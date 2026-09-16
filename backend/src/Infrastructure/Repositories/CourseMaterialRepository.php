<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\CourseMaterial;
use App\Domain\Repositories\CourseMaterialRepositoryInterface;

class CourseMaterialRepository extends BaseRepository implements CourseMaterialRepositoryInterface
{
    public function paginate(int $userId, ?int $courseId, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $where = "WHERE cm.user_id = :userId";
        if ($courseId !== null) {
            $where .= " AND cm.course_id = :courseId";
        }

        // Main query — use bindValue with PARAM_INT for LIMIT/OFFSET (required when
        // ATTR_EMULATE_PREPARES is false; MySQL native protocol rejects string-typed integers).
        $stmt = $this->db->prepare("
            SELECT cm.*, c.code as course_code, c.name as course_name
            FROM course_materials cm
            JOIN courses c ON cm.course_id = c.id
            {$where}
            ORDER BY cm.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
        if ($courseId !== null) {
            $stmt->bindValue(':courseId', $courseId, \PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Count query — reuse the same $where clause and placeholder names
        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) as total FROM course_materials cm {$where}"
        );
        $countStmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
        if ($courseId !== null) {
            $countStmt->bindValue(':courseId', $courseId, \PDO::PARAM_INT);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetch(\PDO::FETCH_ASSOC)['total'];

        return [
            'items' => array_map(fn($row) => [
                'id'           => (int) $row['id'],
                'user_id'      => (int) $row['user_id'],
                'course_id'    => (int) $row['course_id'],
                'meeting_name' => $row['meeting_name'],
                'file_path'    => $row['file_path'],
                'file_name'    => $row['file_name'],
                'file_size'    => (int) $row['file_size'],
                'mime_type'    => $row['mime_type'],
                'created_at'   => $row['created_at'],
                'updated_at'   => $row['updated_at'],
                'course'       => [
                    'id'   => (int) $row['course_id'],
                    'code' => $row['course_code'],
                    'name' => $row['course_name'],
                ],
            ], $items),
            'pagination' => [
                'page'        => $page,
                'limit'       => $limit,
                'total'       => $total,
                'total_pages' => (int) ceil($total / $limit),
            ],
        ];
    }

    public function findById(int $id, int $userId): ?CourseMaterial
    {
        $stmt = $this->db->prepare("
            SELECT * FROM course_materials 
            WHERE id = :id AND user_id = :userId
        ");
        $stmt->execute(['id' => $id, 'userId' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? CourseMaterial::fromArray($row) : null;
    }

    public function create(
        int $userId,
        int $courseId,
        string $meetingName,
        string $filePath,
        string $fileName,
        int $fileSize,
        ?string $mimeType
    ): CourseMaterial {
        $stmt = $this->db->prepare("
            INSERT INTO course_materials (user_id, course_id, meeting_name, file_path, file_name, file_size, mime_type)
            VALUES (:userId, :courseId, :meetingName, :filePath, :fileName, :fileSize, :mimeType)
        ");
        $stmt->execute([
            'userId'      => $userId,
            'courseId'    => $courseId,
            'meetingName' => $meetingName,
            'filePath'    => $filePath,
            'fileName'    => $fileName,
            'fileSize'    => $fileSize,
            'mimeType'    => $mimeType,
        ]);

        $id = (int) $this->db->lastInsertId();
        return $this->findById($id, $userId);
    }

    public function delete(int $id, int $userId): bool
    {
        // First get the file path to delete the actual file
        $material = $this->findById($id, $userId);
        if (!$material) {
            return false;
        }

        // Delete the physical file
        $fullPath = dirname(__DIR__, 3) . '/storage/materials/' . $material->filePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $stmt = $this->db->prepare("DELETE FROM course_materials WHERE id = :id AND user_id = :userId");
        return $stmt->execute(['id' => $id, 'userId' => $userId]);
    }
}