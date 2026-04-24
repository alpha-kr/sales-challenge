<?php

namespace App\Http\Controllers;

use App\Domain\Services\Actions\CreateServiceAction;
use App\Domain\Services\Actions\DeleteServiceAction;
use App\Domain\Services\Actions\UpdateServiceAction;
use App\Domain\Services\DTOs\CreateServiceData;
use App\Domain\Services\DTOs\UpdateServiceData;
use App\Domain\Services\Models\Service;
use App\Http\Resources\ServiceResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ServiceController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(
            data: ServiceResource::collection(Service::with('requiredProduct')->orderBy('id', 'desc')->get()),
            message: 'Services retrieved successfully.'
        );
    }

    public function store(
        CreateServiceAction $action,
        CreateServiceData $dto
    ): JsonResponse {
        return $this->success(
            data: new ServiceResource($action->execute($dto)->load('requiredProduct')),
            message: 'Service created successfully.',
            statusCode: 201
        );
    }

    public function show(Service $service): JsonResponse
    {
        return $this->success(
            data: new ServiceResource($service->load('requiredProduct')),
            message: 'Service retrieved successfully.'
        );
    }

    public function update(
        Service $service,
        UpdateServiceAction $action,
        UpdateServiceData $dto
    ): JsonResponse {
        return $this->success(
            data: new ServiceResource($action->execute($dto, $service)->load('requiredProduct')),
            message: 'Service updated successfully.'
        );
    }

    public function destroy(Service $service, DeleteServiceAction $action): Response
    {
        $action->execute($service);

        return response()->noContent();
    }
}
