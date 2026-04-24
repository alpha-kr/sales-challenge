<?php

namespace App\Domain\Sales\DTOs;

use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

class ListSalesData extends Data
{
    public function __construct(
        #[Exists('clients', 'id')]
        public readonly ?int $client_id = null,
        #[DateFormat('Y-m-d')]
        public readonly ?string $date_from = null,
        #[DateFormat('Y-m-d')]
        public readonly ?string $date_to = null,
        #[Min(1), Max(100)]
        public readonly int $per_page = 15,
    ) {}
}
