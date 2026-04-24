<?php

namespace App\Domain\Clients\Models;

use App\Domain\Sales\Models\Sale;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'tax_id'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return ClientFactory::new();
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
