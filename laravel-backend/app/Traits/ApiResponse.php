<?php

namespace App\Traits;

use App\Domain\Shared\Enums\ApiErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

trait ApiResponse
{
    protected function success(mixed $data, string $message = '', int $statusCode = 200): JsonResponse
    {
        if ($data instanceof JsonResource) {
            $resolved = $data->toResponse(request())->getData(true);

            return response()->json([
                'success' => true,
                'data' => $resolved['data'] ?? $resolved,
                'message' => $message,
                'meta' => $resolved['meta'] ?? new \stdClass,
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'meta' => new \stdClass,
        ], $statusCode);
    }

    protected function error(
        string $message,
        ApiErrorCode $errorCode,
        int $statusCode = 400,
        array $details = [],
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $errorCode->value,
                'message' => $message,
                'details' => empty($details) ? new \stdClass : $details,
            ],
        ], $statusCode);
    }
}
