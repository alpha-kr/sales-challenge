<?php

namespace App\Domain\Sales\DTOs;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class SaleItemData extends Data
{
    public function __construct(
        #[Nullable, Exists('products', 'id')]
        public readonly ?int $product_id,
        #[Nullable, Exists('services', 'id')]
        public readonly ?int $service_id,
        #[Required, Min(1)]
        public readonly int $quantity,
    ) {}
}
