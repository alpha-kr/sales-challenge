<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Products\Models\Product;
use App\Domain\Sales\DTOs\CreateSaleData;
use App\Domain\Sales\DTOs\SaleItemData;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SaleDetail;
use App\Domain\Services\Models\Service;
use App\Domain\Shared\Enums\ApiErrorCode;
use App\Exceptions\DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProcessSaleAction
{
    public function execute(CreateSaleData $data): Sale
    {
        return DB::transaction(function () use ($data): Sale {
            $items = $data->items->toCollection();

            $productIds = $items->filter(fn (SaleItemData $i) => $i->product_id !== null)->pluck('product_id')->unique()->values()->all();
            $serviceIds = $items->filter(fn (SaleItemData $i) => $i->service_id !== null)->pluck('service_id')->unique()->values()->all();

            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');
            $services = Service::with('requiredProduct')->whereIn('id', $serviceIds)->get()->keyBy('id');

            foreach ($items as $item) {
                $this->assertProductAvailable($item, $products);
                $this->assertServiceAvailable($item, $services);
            }

            foreach ($productIds as $productId) {
                $this->assertClientLimitNotExceeded($productId, $data->client_id);
            }

            $dailySequence = Sale::where('client_id', $data->client_id)
                ->whereDate('created_at', today())
                ->count() + 1;

            $total = $items->sum(function (SaleItemData $item) use ($products, $services): float {
                $price = $item->product_id !== null
                    ? (float) $products->get($item->product_id)->price
                    : (float) $services->get($item->service_id)->price;

                return $price * $item->quantity;
            });

            $sale = Sale::create([
                'client_id' => $data->client_id,
                'daily_sequence' => $dailySequence,
                'total' => $total,
            ]);

            foreach ($items as $item) {
                $unitPrice = $item->product_id !== null
                    ? (float) $products->get($item->product_id)->price
                    : (float) $services->get($item->service_id)->price;

                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item->product_id,
                    'service_id' => $item->service_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                ]);

                if ($item->product_id !== null) {
                    Product::where('id', $item->product_id)->decrement('stock', $item->quantity);
                }
            }

            return $sale->load('details');
        });
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function assertProductAvailable(SaleItemData $item, Collection $products): void
    {
        if ($item->product_id === null) {
            return;
        }

        $product = $products->get($item->product_id);

        if ($product === null || $product->stock <= 0) {
            throw new DomainException(
                errorCode: ApiErrorCode::InsufficientStock,
                message: "Product {$item->product_id} is out of stock.",
            );
        }
    }

    /**
     * @param  Collection<int, Service>  $services
     */
    private function assertServiceAvailable(SaleItemData $item, Collection $services): void
    {
        if ($item->service_id === null) {
            return;
        }

        $service = $services->get($item->service_id);

        if ($service === null || $service->disabled_at !== null) {
            throw new DomainException(
                errorCode: ApiErrorCode::ServiceDependencyFailed,
                message: "Service {$item->service_id} is not available.",
            );
        }

        if ($service->required_product_id !== null) {
            $requiredProduct = $service->requiredProduct;

            if ($requiredProduct === null || $requiredProduct->stock <= 0) {
                throw new DomainException(
                    errorCode: ApiErrorCode::ServiceDependencyFailed,
                    message: "Service {$item->service_id} requires product {$service->required_product_id} to be in stock.",
                );
            }
        }
    }

    private function assertClientLimitNotExceeded(int $productId, int $clientId): void
    {
        $distinctClientIds = Sale::join('sale_details', 'sales.id', '=', 'sale_details.sale_id')
            ->whereDate('sales.created_at', today())
            ->where('sale_details.product_id', $productId)
            ->distinct()
            ->pluck('sales.client_id');

        if ($distinctClientIds->count() >= 3 && ! $distinctClientIds->contains($clientId)) {
            throw new DomainException(
                errorCode: ApiErrorCode::DailyLimitReached,
                message: "Product {$productId} has already been sold to 3 distinct clients today.",
            );
        }
    }
}
