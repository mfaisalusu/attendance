<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\UseCases\Master\GetMasterDataUseCase;
use App\Infrastructure\Repositories\JsonMasterRepository;
use App\Presentation\Requests\Request;
use App\Presentation\Responses\JsonResponse;
use Throwable;

class MasterController extends BaseController
{
    private GetMasterDataUseCase $useCase;

    public function __construct()
    {
        $this->useCase = new GetMasterDataUseCase(new JsonMasterRepository());
    }

    // GET /api/master/departments
    public function departments(Request $request): void
    {
        try {
            JsonResponse::success($this->useCase->getDepartments());
        } catch (Throwable $e) {
            JsonResponse::error('Gagal memuat data jurusan.', 500);
        }
    }

    // GET /api/master/courses
    public function courses(Request $request): void
    {
        try {
            JsonResponse::success($this->useCase->getCourses());
        } catch (Throwable $e) {
            JsonResponse::error('Gagal memuat data mata kuliah.', 500);
        }
    }

    // GET /api/master/classes
    public function classes(Request $request): void
    {
        try {
            JsonResponse::success($this->useCase->getClasses());
        } catch (Throwable $e) {
            JsonResponse::error('Gagal memuat data kelas.', 500);
        }
    }

    // GET /api/master/semesters
    public function semesters(Request $request): void
    {
        try {
            JsonResponse::success($this->useCase->getSemesters());
        } catch (Throwable $e) {
            JsonResponse::error('Gagal memuat data semester.', 500);
        }
    }
}
