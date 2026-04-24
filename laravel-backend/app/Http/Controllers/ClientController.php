<?php

namespace App\Http\Controllers;

use App\Domain\Clients\Actions\CreateClientAction;
use App\Domain\Clients\Actions\DeleteClientAction;
use App\Domain\Clients\Actions\UpdateClientAction;
use App\Domain\Clients\DTOs\CreateClientData;
use App\Domain\Clients\DTOs\UpdateClientData;
use App\Domain\Clients\Models\Client;
use App\Http\Resources\ClientResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ClientController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(
            data: ClientResource::collection(Client::orderBy('id', 'desc')->get()),
            message: 'Clients retrieved successfully.'
        );
    }

    public function store(
        CreateClientAction $action,
        CreateClientData $dto
    ): JsonResponse {
        return $this->success(
            data: new ClientResource($action->execute($dto)),
            message: 'Client created successfully.',
            statusCode: 201
        );
    }

    public function show(Client $client): JsonResponse
    {
        return $this->success(
            data: new ClientResource($client),
            message: 'Client retrieved successfully.'
        );
    }

    public function update(
        Client $client,
        UpdateClientAction $action,
        UpdateClientData $dto
    ): JsonResponse {
        return $this->success(
            data: new ClientResource($action->execute($dto, $client)),
            message: 'Client updated successfully.'
        );
    }

    public function destroy(Client $client, DeleteClientAction $action): Response
    {
        $action->execute($client);

        return response()->noContent();
    }
}
