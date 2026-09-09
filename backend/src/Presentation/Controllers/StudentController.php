<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\DTO\StudentDTO;
use App\Application\UseCases\Student\CreateStudentUseCase;
use App\Application\UseCases\Student\DeleteStudentUseCase;
use App\Application\UseCases\Student\GetStudentUseCase;
use App\Application\UseCases\Student\ListStudentsUseCase;
use App\Application\UseCases\Student\UpdateStudentUseCase;
use App\Infrastructure\Repositories\DatabaseMasterRepository;
use App\Infrastructure\Repositories\StudentRepository;
use App\Presentation\Requests\Request;
use App\Presentation\Requests\Validator;
use App\Presentation\Responses\JsonResponse;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class StudentController extends BaseController
{
    // GET /api/students
    public function index(Request $request): void
    {
        $userId  = $this->authUserId();
        $page    = $request->queryInt('page', 1);
        $limit   = $request->queryInt('limit', 20);
        $search  = $request->query('search');
        $classId = $request->query('class_id') !== null ? (int) $request->query('class_id') : null;

        try {
            $result = (new ListStudentsUseCase(new StudentRepository(), new DatabaseMasterRepository()))
                ->execute($userId, $page, $limit, $search, $classId);
            JsonResponse::success($result);
        } catch (Throwable) { JsonResponse::error('Gagal memuat data mahasiswa.', 500); }
    }

    // GET /api/students/{id}
    public function show(Request $request): void
    {
        $userId    = $this->authUserId();
        $studentId = (int) $request->param('id');
        try {
            $data = (new GetStudentUseCase(new StudentRepository(), new DatabaseMasterRepository()))
                ->execute($studentId, $userId);
            JsonResponse::success($data);
        } catch (RuntimeException $e) { JsonResponse::notFound($e->getMessage()); }
        catch (Throwable) { JsonResponse::error('Gagal memuat data mahasiswa.', 500); }
    }

    // POST /api/students
    public function store(Request $request): void
    {
        $userId = $this->authUserId();
        $data   = $request->all();
        $v      = new Validator();
        if (!$v->validate($data, [
            'nip'      => 'required|max:30',
            'name'     => 'required|max:100',
            'class_id' => 'required|integer',
        ])) { JsonResponse::unprocessable($v->errors()); }

        try {
            $student = (new CreateStudentUseCase(new StudentRepository(), new DatabaseMasterRepository()))
                ->execute($userId, new StudentDTO(
                    nip:     $data['nip'],
                    name:    $data['name'],
                    classId: (int) $data['class_id'],
                ));
            JsonResponse::created($student, 'Mahasiswa berhasil ditambahkan.');
        } catch (InvalidArgumentException $e) { JsonResponse::unprocessable(['general' => [$e->getMessage()]]); }
        catch (Throwable) { JsonResponse::error('Gagal menambahkan mahasiswa.', 500); }
    }

    // PUT /api/students/{id}
    public function update(Request $request): void
    {
        $userId    = $this->authUserId();
        $studentId = (int) $request->param('id');
        $data      = $request->all();
        $v         = new Validator();
        if (!$v->validate($data, [
            'nip'      => 'required|max:30',
            'name'     => 'required|max:100',
            'class_id' => 'required|integer',
        ])) { JsonResponse::unprocessable($v->errors()); }

        try {
            $student = (new UpdateStudentUseCase(new StudentRepository(), new DatabaseMasterRepository()))
                ->execute($studentId, $userId, new StudentDTO(
                    nip:     $data['nip'],
                    name:    $data['name'],
                    classId: (int) $data['class_id'],
                ));
            JsonResponse::success($student, 'Data mahasiswa berhasil diperbarui.');
        } catch (InvalidArgumentException $e) { JsonResponse::unprocessable(['general' => [$e->getMessage()]]); }
        catch (RuntimeException $e) { JsonResponse::notFound($e->getMessage()); }
        catch (Throwable) { JsonResponse::error('Gagal memperbarui data mahasiswa.', 500); }
    }

    // DELETE /api/students/{id}
    public function destroy(Request $request): void
    {
        $userId    = $this->authUserId();
        $studentId = (int) $request->param('id');
        try {
            (new DeleteStudentUseCase(new StudentRepository()))->execute($studentId, $userId);
            JsonResponse::success(null, 'Mahasiswa berhasil dihapus.');
        } catch (RuntimeException $e) { JsonResponse::notFound($e->getMessage()); }
        catch (Throwable) { JsonResponse::error('Gagal menghapus mahasiswa.', 500); }
    }
}
