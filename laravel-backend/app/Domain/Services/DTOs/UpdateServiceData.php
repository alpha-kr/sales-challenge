<?php

namespace App\Domain\Services\DTOs;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateServiceData extends Data
{
    public function __construct(
        #[Sometimes, StringType, Max(255)]
        public readonly string $name,
        #[Sometimes, Numeric, Min(0)]
        public readonly float $price,
        #[Nullable]
        public readonly ?string $disabled_at,
        #[Nullable, Exists('products', 'id')]
        public readonly ?int $required_product_id,
    ) {}
}
