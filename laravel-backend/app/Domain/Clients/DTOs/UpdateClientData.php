<?php

namespace App\Domain\Clients\DTOs;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateClientData extends Data
{
    public function __construct(
        #[Sometimes, StringType, Max(255)]
        public readonly string $name,
        #[Sometimes, StringType, Max(255)]
        public readonly string $tax_id,
    ) {}
}
