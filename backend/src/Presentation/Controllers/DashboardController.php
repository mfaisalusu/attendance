<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\UseCases\Dashboard\GetDashboardStatsUseCase;
use App\Infrastructure\Repositories\DashboardRepository;
use App\Presentation\Requests\Request;
use App\Presentation\Responses\JsonResponse;
use Throwable;

class DashboardController extends BaseController
{
    // GET /api/dashboard
    public function index(Request $request): void
    {
        $userId = $this->authUserId();

        try {
            $useCase = new GetDashboardStatsUseCase(new DashboardRepository());
            $stats   = $useCase->execute($userId);

            JsonResponse::success($stats);
        } catch (Throwable) {
            JsonResponse::error('Gagal memuat data dashboard.', 500);
        }
    }
}
