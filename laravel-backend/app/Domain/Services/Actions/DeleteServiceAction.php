<?php

namespace App\Domain\Services\Actions;

use App\Domain\Services\Models\Service;
use App\Domain\Shared\Enums\ApiErrorCode;
use App\Exceptions\DomainException;

class DeleteServiceAction
{
    public function execute(Service $service): void
    {
        if ($service->saleDetails()->exists()) {
            throw new DomainException(
                errorCode: ApiErrorCode::HasActiveSales,
                message: 'Cannot delete a service that has associated sales.',
                statusCode: 409,
            );
        }

        $service->delete();
    }
}
