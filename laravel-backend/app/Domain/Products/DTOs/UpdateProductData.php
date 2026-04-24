<?php

namespace App\Domain\Products\DTOs;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateProductData extends Data
{
    public function __construct(
        #[Sometimes, StringType, Max(255)]
        public readonly string $name,
        #[Sometimes, Numeric, Min(0)]
        public readonly float $price,
        #[Sometimes, IntegerType, Min(0)]
        public readonly int $stock,
    ) {}
}
