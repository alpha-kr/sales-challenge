<?php

namespace App\Domain\Dashboard\QueryObjects;

use App\Domain\Products\Models\Product;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SaleDetail;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardStatsQuery
{
    public function __invoke(): array
    {
        return [
            'products_stock'   => $this->productsStock(),
            'sales_by_month'   => $this->salesByMonth(),
            'sales_by_product' => $this->salesByProduct(),
            'sales_by_service' => $this->salesByService(),
        ];
    }

    private function productsStock(): Collection
    {
        return Product::select('name', 'stock')
            ->orderByDesc('stock')
            ->get()
            ->map(fn (Product $p) => ['name' => $p->name, 'stock' => $p->stock]);
    }

    private function salesByMonth(): Collection
    {
        $monthExpr = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        return Sale::selectRaw("{$monthExpr} as month, SUM(total) as total, COUNT(*) as count")
            ->groupByRaw($monthExpr)
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'label' => Carbon::parse($row->month.'-01')->format('M Y'),
                'total' => (float) $row->total,
                'count' => (int) $row->count,
            ]);
    }

    private function salesByProduct(): Collection
    {
        return SaleDetail::join('products', 'sale_details.product_id', '=', 'products.id')
            ->selectRaw('products.name, SUM(sale_details.quantity * sale_details.unit_price) as total')
            ->whereNotNull('sale_details.product_id')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'total' => (float) $row->total]);
    }

    private function salesByService(): Collection
    {
        return SaleDetail::join('services', 'sale_details.service_id', '=', 'services.id')
            ->selectRaw('services.name, SUM(sale_details.quantity * sale_details.unit_price) as total')
            ->whereNotNull('sale_details.service_id')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'total' => (float) $row->total]);
    }
}
