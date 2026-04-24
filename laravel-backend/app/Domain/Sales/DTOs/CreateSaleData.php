<?php

namespace App\Domain\Sales\DTOs;

use Illuminate\Validation\Validator;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class CreateSaleData extends Data
{
    public function __construct(
        #[Required, Exists('clients', 'id')]
        public readonly int $client_id,
        /** @var DataCollection<int, SaleItemData> */
        #[Required]
        public readonly DataCollection $items,
    ) {}

    public static function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($validator->getData()['items'] ?? [] as $index => $item) {
                if (($item['product_id'] ?? null) === null && ($item['service_id'] ?? null) === null) {
                    $validator->errors()->add(
                        "items.{$index}.product_id",
                        'At least one of product_id or service_id is required.'
                    );
                }
            }
        });
    }
}
