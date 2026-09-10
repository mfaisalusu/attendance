<?php

declare(strict_types=1);

namespace App\Application\UseCases\Attendance;

use App\Application\DTO\AttendanceDTO;
use App\Domain\Entities\Attendance;
use App\Domain\Repositories\AttendanceRepositoryInterface;
use App\Domain\Repositories\StudentRepositoryInterface;
use InvalidArgumentException;

class SaveAttendanceUseCase
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendanceRepository,
        private readonly StudentRepositoryInterface    $studentRepository,
    ) {}

    public function execute(int $userId, AttendanceDTO $dto): void
    {
        if (empty($dto->attendance)) {
            throw new InvalidArgumentException('Data absensi tidak boleh kosong.');
        }

        // Validate all items before persisting
        foreach ($dto->attendance as $index => $item) {
            if (!isset($item['student_id']) || !isset($item['status'])) {
                throw new InvalidArgumentException("Item absensi ke-{$index} tidak valid.");
            }

            if (!in_array($item['status'], Attendance::STATUSES, true)) {
                throw new InvalidArgumentException(
                    "Status '{$item['status']}' tidak valid. Gunakan: " . implode(', ', Attendance::STATUSES)
                );
            }

            $student = $this->studentRepository->findById((int) $item['student_id'], $userId);
            if ($student === null) {
                throw new InvalidArgumentException(
                    "Mahasiswa dengan ID {$item['student_id']} tidak ditemukan."
                );
            }
        }

        $this->attendanceRepository->bulkUpsert($userId, $dto->date, $dto->courseId, $dto->attendance);
    }
}
