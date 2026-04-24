<?php

namespace App\Domain\Clients\Actions;

use App\Domain\Clients\DTOs\CreateClientData;
use App\Domain\Clients\Models\Client;

class CreateClientAction
{
    public function execute(CreateClientData $data): Client
    {
        return Client::create($data->toArray());
    }
}
