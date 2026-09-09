<?php

declare(strict_types=1);

namespace App\Application\UseCases\Attendance;

use App\Domain\Entities\Attendance;
use App\Domain\Repositories\AttendanceRepositoryInterface;
use InvalidArgumentException;
use RuntimeException;

class UpdateAttendanceUseCase
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendanceRepository,
    ) {}

    public function execute(int $id, int $userId, string $status): array
    {
        if (!in_array($status, Attendance::STATUSES, true)) {
            throw new InvalidArgumentException(
                "Status '{$status}' tidak valid. Gunakan: " . implode(', ', Attendance::STATUSES)
            );
        }

        $record = $this->attendanceRepository->update($id, $userId, $status);

        if ($record === null) {
            throw new RuntimeException('Data absensi tidak ditemukan.');
        }

        return $record->toArray();
    }
}
