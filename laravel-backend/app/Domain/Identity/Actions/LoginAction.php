<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\DTOs\LoginData;
use App\Domain\Identity\Enums\LoginType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginAction
{
    public function execute(LoginData $data): ?string
    {
        if (! Auth::attempt(['email' => $data->email, 'password' => $data->password])) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($data->type === LoginType::Token) {
            return Auth::user()->createToken('api')->plainTextToken;
        }

        return null;
    }
}
