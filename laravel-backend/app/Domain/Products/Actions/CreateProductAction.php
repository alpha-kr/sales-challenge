<?php

namespace App\Domain\Products\Actions;

use App\Domain\Products\DTOs\CreateProductData;
use App\Domain\Products\Models\Product;

class CreateProductAction
{
    public function execute(CreateProductData $data): Product
    {
        return Product::create($data->toArray());
    }
}
