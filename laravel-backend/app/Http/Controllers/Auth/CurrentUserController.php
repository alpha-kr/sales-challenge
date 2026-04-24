<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CurrentUserController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        return $this->success(new UserResource(auth()->user()), 'Authenticated user.');
    }
}
