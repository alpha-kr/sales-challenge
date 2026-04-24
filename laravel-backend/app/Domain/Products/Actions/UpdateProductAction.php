<?php

namespace App\Domain\Products\Actions;

use App\Domain\Products\DTOs\UpdateProductData;
use App\Domain\Products\Models\Product;

class UpdateProductAction
{
    public function execute(UpdateProductData $data, Product $product): Product
    {
        $product->update(array_filter($data->toArray(), fn ($value) => $value !== null));

        return $product->fresh();
    }
}
