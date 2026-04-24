<?php

namespace App\Domain\Clients\Actions;

use App\Domain\Clients\Models\Client;
use App\Domain\Shared\Enums\ApiErrorCode;
use App\Exceptions\DomainException;

class DeleteClientAction
{
    public function execute(Client $client): void
    {
        if ($client->sales()->exists()) {
            throw new DomainException(
                errorCode: ApiErrorCode::HasActiveSales,
                message: 'Cannot delete a client that has associated sales.',
                statusCode: 409,
            );
        }

        $client->delete();
    }
}
