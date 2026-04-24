<?php

namespace App\Domain\Services\Actions;

use App\Domain\Services\DTOs\CreateServiceData;
use App\Domain\Services\Models\Service;

class CreateServiceAction
{
    public function execute(CreateServiceData $data): Service
    {
        return Service::create($data->toArray());
    }
}
