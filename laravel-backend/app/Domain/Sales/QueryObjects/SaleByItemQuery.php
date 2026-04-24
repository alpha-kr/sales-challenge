<?php

namespace App\Domain\Sales\QueryObjects;

use App\Domain\Sales\DTOs\ListSalesData;
use App\Domain\Sales\Enums\SaleItem;
use App\Domain\Sales\Models\Sale;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SaleByItemQuery
{
    public function __invoke(ListSalesData $filters, SaleItem $type): Builder
    {
        return DB::table('sales')
            ->select(
                'products.name',
                DB::raw('SUM(sale_details.quantity * sale_details.price) as total_sales')
            )->when($type === SaleItem::PRODUCT, function (Builder $query) {
                $query->join('products', 'sale_details.product_id', '=', 'products.id')
                 ->groupBy('product_id');
            })
            ->when($type === SaleItem::SERVICE, function (Builder $query) {
                $query->join('services', 'sale_details.service_id', '=', 'services.id')
                 ->groupBy('service_id');
            })
            ->join('sale_details', 'sales.id', '=', 'sale_details.sale_id')
            ->when($filters->client_id, fn (Builder $query) => $query->where('client_id', $filters->client_id))
            ->when($filters->date_from, fn (Builder $query) => $query->whereDate('created_at', '>=', $filters->date_from))
            ->when($filters->date_to, fn (Builder $query) => $query->whereDate('created_at', '<=', $filters->date_to));
    }
}
