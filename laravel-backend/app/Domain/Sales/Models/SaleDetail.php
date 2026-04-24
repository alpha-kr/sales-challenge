<?php

namespace App\Domain\Sales\Models;

use App\Domain\Products\Models\Product;
use App\Domain\Services\Models\Service;
use Database\Factories\SaleDetailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sale_id', 'product_id', 'service_id', 'quantity', 'unit_price'])]
class SaleDetail extends Model
{
    /** @use HasFactory<SaleDetailFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return SaleDetailFactory::new();
    }

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
