<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Actions\LoginAction;
use App\Domain\Identity\DTOs\LoginData;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request, LoginAction $action): JsonResponse
    {
        $data = LoginData::from($request);

        $action->execute($data);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return $this->success([], 'Login successful.');
    }
}
