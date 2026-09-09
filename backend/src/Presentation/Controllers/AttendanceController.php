<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\DTO\AttendanceDTO;
use App\Application\UseCases\Attendance\GetAttendanceUseCase;
use App\Application\UseCases\Attendance\MonthlyRecapUseCase;
use App\Application\UseCases\Attendance\SaveAttendanceUseCase;
use App\Application\UseCases\Attendance\UpdateAttendanceUseCase;
use App\Infrastructure\Repositories\AttendanceRepository;
use App\Infrastructure\Repositories\StudentRepository;
use App\Presentation\Requests\Request;
use App\Presentation\Requests\Validator;
use App\Presentation\Responses\JsonResponse;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AttendanceController extends BaseController
{
    // ------------------------------------------------------------------
    // GET /api/attendance
    // Query: date, department_id, course_id, class_id, semester_id
    // ------------------------------------------------------------------
    public function index(Request $request): void
    {
        $userId = $this->authUserId();
        $date   = $request->query('date') ?? date('Y-m-d');

        $departmentId = $this->optionalInt($request, 'department_id');
        $courseId     = $this->optionalInt($request, 'course_id');
        $classId      = $this->optionalInt($request, 'class_id');
        $semesterId   = $this->optionalInt($request, 'semester_id');

        try {
            $useCase = new GetAttendanceUseCase(
                new AttendanceRepository(),
                new StudentRepository(),
            );

            $result = $useCase->execute(
                $userId, $date, $departmentId, $courseId, $classId, $semesterId
            );

            JsonResponse::success($result);
        } catch (Throwable) {
            JsonResponse::error('Gagal memuat data absensi.', 500);
        }
    }

    // ------------------------------------------------------------------
    // POST /api/attendance
    // Body: { date, attendance: [{student_id, status}] }
    // ------------------------------------------------------------------
    public function store(Request $request): void
    {
        $userId    = $this->authUserId();
        $data      = $request->all();
        $validator = new Validator();

        $valid = $validator->validate($data, [
            'date'       => 'required|date',
            'attendance' => 'required|array',
        ]);

        if (!$valid) {
            JsonResponse::unprocessable($validator->errors());
        }

        try {
            $useCase = new SaveAttendanceUseCase(
                new AttendanceRepository(),
                new StudentRepository(),
            );

            $useCase->execute($userId, new AttendanceDTO(
                date:       $data['date'],
                attendance: $data['attendance'],
            ));

            JsonResponse::success(null, 'Absensi berhasil disimpan.');
        } catch (InvalidArgumentException $e) {
            JsonResponse::unprocessable(['attendance' => [$e->getMessage()]]);
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 500);
        } catch (Throwable) {
            JsonResponse::error('Gagal menyimpan absensi.', 500);
        }
    }

    // ------------------------------------------------------------------
    // PUT /api/attendance/{id}
    // Body: { status }
    // ------------------------------------------------------------------
    public function update(Request $request): void
    {
        $userId       = $this->authUserId();
        $attendanceId = (int) $request->param('id');
        $data         = $request->all();
        $validator    = new Validator();

        $valid = $validator->validate($data, [
            'status' => 'required|in:hadir,izin,sakit,alpha',
        ]);

        if (!$valid) {
            JsonResponse::unprocessable($validator->errors());
        }

        try {
            $useCase = new UpdateAttendanceUseCase(new AttendanceRepository());
            $result  = $useCase->execute($attendanceId, $userId, $data['status']);

            JsonResponse::success($result, 'Absensi berhasil diperbarui.');
        } catch (InvalidArgumentException $e) {
            JsonResponse::unprocessable(['status' => [$e->getMessage()]]);
        } catch (RuntimeException $e) {
            JsonResponse::notFound($e->getMessage());
        } catch (Throwable) {
            JsonResponse::error('Gagal memperbarui absensi.', 500);
        }
    }

    // ------------------------------------------------------------------
    // GET /api/attendance/recap
    // Query: year, month, department_id, course_id, class_id, semester_id
    // ------------------------------------------------------------------
    public function recap(Request $request): void
    {
        $userId    = $this->authUserId();
        $validator = new Validator();

        $params = [
            'year'  => $request->query('year'),
            'month' => $request->query('month'),
        ];

        $valid = $validator->validate($params, [
            'year'  => 'required|integer',
            'month' => 'required|integer',
        ]);

        if (!$valid) {
            JsonResponse::unprocessable($validator->errors());
        }

        $year         = (int) $params['year'];
        $month        = (int) $params['month'];
        $departmentId = $this->optionalInt($request, 'department_id');
        $courseId     = $this->optionalInt($request, 'course_id');
        $classId      = $this->optionalInt($request, 'class_id');
        $semesterId   = $this->optionalInt($request, 'semester_id');

        if ($month < 1 || $month > 12) {
            JsonResponse::unprocessable(['month' => ['Bulan harus antara 1 dan 12.']]);
        }

        try {
            $useCase = new MonthlyRecapUseCase(new AttendanceRepository());
            $result  = $useCase->execute(
                $userId, $year, $month,
                $departmentId, $courseId, $classId, $semesterId
            );

            JsonResponse::success($result);
        } catch (Throwable) {
            JsonResponse::error('Gagal memuat rekap absensi.', 500);
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function optionalInt(Request $request, string $key): ?int
    {
        $val = $request->query($key);
        return ($val !== null && $val !== '') ? (int) $val : null;
    }
}
