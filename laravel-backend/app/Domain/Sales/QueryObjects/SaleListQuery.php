<?php

namespace App\Domain\Sales\QueryObjects;

use App\Domain\Sales\DTOs\ListSalesData;
use App\Domain\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Builder;

class SaleListQuery
{
    public function __invoke(ListSalesData $filters): Builder
    {
        return Sale::query()
            ->with(['client', 'details'])
            ->when($filters->client_id, fn (Builder $query) => $query->where('client_id', $filters->client_id))
            ->when($filters->date_from, fn (Builder $query) => $query->whereDate('created_at', '>=', $filters->date_from))
            ->when($filters->date_to, fn (Builder $query) => $query->whereDate('created_at', '<=', $filters->date_to))
            ->orderBy('created_at', 'desc');
    }
}
