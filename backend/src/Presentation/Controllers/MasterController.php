<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\UseCases\Master\GetMasterDataUseCase;
use App\Infrastructure\Repositories\DatabaseMasterRepository;
use App\Presentation\Requests\Request;
use App\Presentation\Requests\Validator;
use App\Presentation\Responses\JsonResponse;
use RuntimeException;
use Throwable;

class MasterController extends BaseController
{
    private GetMasterDataUseCase $useCase;

    public function __construct()
    {
        $this->useCase = new GetMasterDataUseCase(new DatabaseMasterRepository());
    }

    // ------------------------------------------------------------------
    // GET /api/master/years  (global — tidak per-user)
    // ------------------------------------------------------------------
    public function years(Request $request): void
    {
        try {
            JsonResponse::success($this->useCase->getYears());
        } catch (Throwable) {
            JsonResponse::error('Gagal memuat data tahun.', 500);
        }
    }

    // ------------------------------------------------------------------
    // GET /api/master/semesters  (global — tidak per-user)
    // ------------------------------------------------------------------
    public function semesters(Request $request): void
    {
        try {
            JsonResponse::success($this->useCase->getSemesters());
        } catch (Throwable) {
            JsonResponse::error('Gagal memuat data semester.', 500);
        }
    }

    // ------------------------------------------------------------------
    // Departments
    // ------------------------------------------------------------------

    public function departments(Request $request): void
    {
        try {
            JsonResponse::success($this->useCase->getDepartments($this->authUserId()));
        } catch (Throwable) {
            JsonResponse::error('Gagal memuat data jurusan.', 500);
        }
    }

    public function storeDepartment(Request $request): void
    {
        $data      = $request->all();
        $validator = new Validator();

        if (!$validator->validate($data, [
            'name' => 'required|max:100',
            'code' => 'required|max:20',
        ])) {
            JsonResponse::unprocessable($validator->errors());
        }

        try {
            $item = $this->useCase->createDepartment($data['name'], $data['code'], $this->authUserId());
            JsonResponse::created($item, 'Jurusan berhasil ditambahkan.');
        } catch (Throwable) {
            JsonResponse::error('Gagal menyimpan data jurusan.', 500);
        }
    }

    public function updateDepartment(Request $request): void
    {
        $id        = (int) $request->param('id');
        $data      = $request->all();
        $validator = new Validator();

        if (!$validator->validate($data, [
            'name' => 'required|max:100',
            'code' => 'required|max:20',
        ])) {
            JsonResponse::unprocessable($validator->errors());
        }

        try {
            $item = $this->useCase->updateDepartment($id, $data['name'], $data['code'], $this->authUserId());
            JsonResponse::success($item, 'Jurusan berhasil diperbarui.');
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 403);
        } catch (Throwable) {
            JsonResponse::error('Gagal memperbarui data jurusan.', 500);
        }
    }

    public function destroyDepartment(Request $request): void
    {
        $id = (int) $request->param('id');
        try {
            $this->useCase->deleteDepartment($id, $this->authUserId());
            JsonResponse::success(null, 'Jurusan berhasil dihapus.');
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 403);
        } catch (Throwable) {
            JsonResponse::error('Gagal menghapus data jurusan.', 500);
        }
    }

    // ------------------------------------------------------------------
    // Courses
    // ------------------------------------------------------------------

    public function courses(Request $request): void
    {
        try {
            JsonResponse::success($this->useCase->getCourses($this->authUserId()));
        } catch (Throwable) {
            JsonResponse::error('Gagal memuat data mata kuliah.', 500);
        }
    }

    public function storeCourse(Request $request): void
    {
        $data      = $request->all();
        $validator = new Validator();

        if (!$validator->validate($data, [
            'name' => 'required|max:150',
            'code' => 'required|max:20',
        ])) {
            JsonResponse::unprocessable($validator->errors());
        }

        try {
            $item = $this->useCase->createCourse($data['name'], $data['code'], $this->authUserId());
            JsonResponse::created($item, 'Mata kuliah berhasil ditambahkan.');
        } catch (Throwable) {
            JsonResponse::error('Gagal menyimpan data mata kuliah.', 500);
        }
    }

    public function updateCourse(Request $request): void
    {
        $id        = (int) $request->param('id');
        $data      = $request->all();
        $validator = new Validator();

        if (!$validator->validate($data, [
            'name' => 'required|max:150',
            'code' => 'required|max:20',
        ])) {
            JsonResponse::unprocessable($validator->errors());
        }

        try {
            $item = $this->useCase->updateCourse($id, $data['name'], $data['code'], $this->authUserId());
            JsonResponse::success($item, 'Mata kuliah berhasil diperbarui.');
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 403);
        } catch (Throwable) {
            JsonResponse::error('Gagal memperbarui data mata kuliah.', 500);
        }
    }

    public function destroyCourse(Request $request): void
    {
        $id = (int) $request->param('id');
        try {
            $this->useCase->deleteCourse($id, $this->authUserId());
            JsonResponse::success(null, 'Mata kuliah berhasil dihapus.');
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 403);
        } catch (Throwable) {
            JsonResponse::error('Gagal menghapus data mata kuliah.', 500);
        }
    }

    // ------------------------------------------------------------------
    // Classes
    // ------------------------------------------------------------------

    public function classes(Request $request): void
    {
        try {
            JsonResponse::success($this->useCase->getClasses($this->authUserId()));
        } catch (Throwable) {
            JsonResponse::error('Gagal memuat data kelas.', 500);
        }
    }

    public function storeClass(Request $request): void
    {
        $data      = $request->all();
        $validator = new Validator();

        if (!$validator->validate($data, [
            'name'        => 'required|max:100',
            'code'        => 'required|max:20',
            'semester_id' => 'required|integer',
            'year'        => 'required|integer',
        ])) {
            JsonResponse::unprocessable($validator->errors());
        }

        try {
            $item = $this->useCase->createClass(
                $data['name'],
                $data['code'],
                (int) $data['semester_id'],
                (int) $data['year'],
                $this->authUserId(),
            );
            JsonResponse::created($item, 'Kelas berhasil ditambahkan.');
        } catch (Throwable) {
            JsonResponse::error('Gagal menyimpan data kelas.', 500);
        }
    }

    public function updateClass(Request $request): void
    {
        $id        = (int) $request->param('id');
        $data      = $request->all();
        $validator = new Validator();

        if (!$validator->validate($data, [
            'name'        => 'required|max:100',
            'code'        => 'required|max:20',
            'semester_id' => 'required|integer',
            'year'        => 'required|integer',
        ])) {
            JsonResponse::unprocessable($validator->errors());
        }

        try {
            $item = $this->useCase->updateClass(
                $id,
                $data['name'],
                $data['code'],
                (int) $data['semester_id'],
                (int) $data['year'],
                $this->authUserId(),
            );
            JsonResponse::success($item, 'Kelas berhasil diperbarui.');
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 403);
        } catch (Throwable) {
            JsonResponse::error('Gagal memperbarui data kelas.', 500);
        }
    }

    public function destroyClass(Request $request): void
    {
        $id = (int) $request->param('id');
        try {
            $this->useCase->deleteClass($id, $this->authUserId());
            JsonResponse::success(null, 'Kelas berhasil dihapus.');
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 403);
        } catch (Throwable) {
            JsonResponse::error('Gagal menghapus data kelas.', 500);
        }
    }
}
