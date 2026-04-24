<?php

namespace App\Domain\Clients\Actions;

use App\Domain\Clients\DTOs\UpdateClientData;
use App\Domain\Clients\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateClientAction
{
    public function execute(UpdateClientData $data, Client $client): Client
    {
        if (isset($data->tax_id) && $data->tax_id !== $client->tax_id) {
            $exists = DB::table('clients')
                ->where('tax_id', $data->tax_id)
                ->where('id', '!=', $client->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'tax_id' => ['The tax ID has already been taken.'],
                ]);
            }
        }

        $client->update(array_filter($data->toArray(), fn ($value) => $value !== null));

        return $client->fresh();
    }
}
