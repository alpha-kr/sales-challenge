<?php

namespace App\Http\Controllers;

use App\Domain\Products\Actions\CreateProductAction;
use App\Domain\Products\Actions\DeleteProductAction;
use App\Domain\Products\Actions\UpdateProductAction;
use App\Domain\Products\DTOs\CreateProductData;
use App\Domain\Products\DTOs\UpdateProductData;
use App\Domain\Products\Models\Product;
use App\Http\Resources\ProductResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(
            data: ProductResource::collection(Product::orderBy('id', 'desc')->get()),
            message: 'Products retrieved successfully.'
        );
    }

    public function store(
        CreateProductAction $action,
        CreateProductData $dto
    ): JsonResponse {
        return $this->success(
            data: new ProductResource($action->execute($dto)),
            message: 'Product created successfully.',
            statusCode: 201
        );
    }

    public function show(Product $product): JsonResponse
    {
        return $this->success(
            data: new ProductResource($product),
            message: 'Product retrieved successfully.'
        );
    }

    public function update(
        Product $product,
        UpdateProductAction $action,
        UpdateProductData $dto
    ): JsonResponse {
        return $this->success(
            data: new ProductResource($action->execute($dto, $product)),
            message: 'Product updated successfully.'
        );
    }

    public function destroy(Product $product, DeleteProductAction $action): Response
    {
        $action->execute($product);

        return response()->noContent();
    }
}
