<?php

namespace App\Http\Controllers;

use App\Domain\Dashboard\QueryObjects\DashboardStatsQuery;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(DashboardStatsQuery $query): JsonResponse
    {
        return $this->success(
            data: $query(),
            message: 'Dashboard data retrieved successfully.'
        );
    }
}
