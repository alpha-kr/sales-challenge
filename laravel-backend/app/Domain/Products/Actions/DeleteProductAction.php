<?php

namespace App\Domain\Products\Actions;

use App\Domain\Products\Models\Product;
use App\Domain\Shared\Enums\ApiErrorCode;
use App\Exceptions\DomainException;

class DeleteProductAction
{
    public function execute(Product $product): void
    {
        if ($product->saleDetails()->exists()) {
            throw new DomainException(
                errorCode: ApiErrorCode::HasActiveSales,
                message: 'Cannot delete a product that has associated sales.',
                statusCode: 409,
            );
        }

        $product->delete();
    }
}
