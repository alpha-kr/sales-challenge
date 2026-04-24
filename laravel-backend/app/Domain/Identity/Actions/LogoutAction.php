<?php

namespace App\Domain\Identity\Actions;

use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutAction
{
    public function execute(): void
    {
        $token = Auth::user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();

            return;
        }

        Auth::guard('web')->logout();
    }
}
