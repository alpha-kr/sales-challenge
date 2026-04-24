<?php

namespace App\Domain\Services\Models;

use App\Domain\Products\Models\Product;
use App\Domain\Sales\Models\SaleDetail;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'price', 'disabled_at', 'required_product_id'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return ServiceFactory::new();
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'disabled_at' => 'datetime',
        ];
    }

    public function requiredProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'required_product_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereNull('disabled_at');
    }

    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }
}
