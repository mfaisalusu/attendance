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
    // GET /api/attendance
    public function index(Request $request): void
    {
        $userId   = $this->authUserId();
        $date     = $request->query('date') ?? date('Y-m-d');
        $classId  = $this->optInt($request, 'class_id');
        $courseId = $this->optInt($request, 'course_id');

        try {
            $result = (new GetAttendanceUseCase(new AttendanceRepository(), new StudentRepository()))
                ->execute($userId, $date, $classId, $courseId);
            JsonResponse::success($result);
        } catch (Throwable) { JsonResponse::error('Gagal memuat data absensi.', 500); }
    }

    // POST /api/attendance
    public function store(Request $request): void
    {
        $userId = $this->authUserId();
        $data   = $request->all();
        $v      = new Validator();
        if (!$v->validate($data, [
            'date'       => 'required|date',
            'course_id'  => 'required|integer',
            'attendance' => 'required|array',
        ])) {
            JsonResponse::unprocessable($v->errors());
        }
        try {
            (new SaveAttendanceUseCase(new AttendanceRepository(), new StudentRepository()))
                ->execute($userId, new AttendanceDTO(
                    date:       $data['date'],
                    courseId:   (int) $data['course_id'],
                    attendance: $data['attendance'],
                ));
            JsonResponse::success(null, 'Absensi berhasil disimpan.');
        } catch (InvalidArgumentException $e) { JsonResponse::unprocessable(['attendance' => [$e->getMessage()]]); }
        catch (RuntimeException $e)           { JsonResponse::error($e->getMessage(), 500); }
        catch (Throwable)                     { JsonResponse::error('Gagal menyimpan absensi.', 500); }
    }

    // PUT /api/attendance/{id}
    public function update(Request $request): void
    {
        $userId       = $this->authUserId();
        $attendanceId = (int) $request->param('id');
        $data         = $request->all();
        $v            = new Validator();
        if (!$v->validate($data, ['status' => 'required|in:hadir,izin,sakit,alpha'])) {
            JsonResponse::unprocessable($v->errors());
        }
        try {
            $result = (new UpdateAttendanceUseCase(new AttendanceRepository()))
                ->execute($attendanceId, $userId, $data['status']);
            JsonResponse::success($result, 'Absensi berhasil diperbarui.');
        } catch (InvalidArgumentException $e) { JsonResponse::unprocessable(['status' => [$e->getMessage()]]); }
        catch (RuntimeException $e)           { JsonResponse::notFound($e->getMessage()); }
        catch (Throwable)                     { JsonResponse::error('Gagal memperbarui absensi.', 500); }
    }

    // GET /api/attendance/recap
    public function recap(Request $request): void
    {
        $userId  = $this->authUserId();
        $v       = new Validator();
        $params  = ['year' => $request->query('year'), 'month' => $request->query('month')];
        if (!$v->validate($params, ['year' => 'required|integer', 'month' => 'required|integer'])) {
            JsonResponse::unprocessable($v->errors());
        }
        $year  = (int) $params['year'];
        $month = (int) $params['month'];
        if ($month < 1 || $month > 12) {
            JsonResponse::unprocessable(['month' => ['Bulan harus antara 1 dan 12.']]);
        }
        $classId  = $this->optInt($request, 'class_id');
        $courseId = $this->optInt($request, 'course_id');

        try {
            $result = (new MonthlyRecapUseCase(new AttendanceRepository()))
                ->execute($userId, $year, $month, $classId, $courseId);
            JsonResponse::success($result);
        } catch (Throwable) { JsonResponse::error('Gagal memuat rekap absensi.', 500); }
    }

    private function optInt(Request $request, string $key): ?int
    {
        $val = $request->query($key);
        return ($val !== null && $val !== '') ? (int) $val : null;
    }
}
