<?php

namespace App\Domain\Services\Actions;

use App\Domain\Services\DTOs\UpdateServiceData;
use App\Domain\Services\Models\Service;

class UpdateServiceAction
{
    public function execute(UpdateServiceData $data, Service $service): Service
    {
        $service->update(array_filter($data->toArray(), fn ($value) => $value !== null));

        return $service->fresh();
    }
}
