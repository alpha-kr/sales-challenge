<?php

namespace App\Http\Controllers;

use App\Domain\Sales\Actions\ProcessSaleAction;
use App\Domain\Sales\DTOs\CreateSaleData;
use App\Domain\Sales\DTOs\ListSalesData;
use App\Domain\Sales\QueryObjects\SaleListQuery;
use App\Http\Resources\SaleResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    use ApiResponse;

    public function index(SaleListQuery $query, ListSalesData $filters): JsonResponse
    {
        return $this->success(
            data: SaleResource::collection($query($filters)->paginate($filters->per_page)),
            message: 'Sales retrieved successfully.'
        );
    }

    public function store(
        ProcessSaleAction $action,
        CreateSaleData $dto
    ): JsonResponse {
        return $this->success(
            data: new SaleResource($action->execute($dto)),
            message: 'Sale processed successfully.',
            statusCode: 201
        );
    }
}
