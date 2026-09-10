<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Student;
use App\Domain\Repositories\StudentRepositoryInterface;

class StudentRepository extends BaseRepository implements StudentRepositoryInterface
{
    public function paginate(
        int     $userId,
        int     $page,
        int     $limit,
        ?string $search  = null,
        ?int    $classId = null,
    ): array {
        $page  = max(1, $page);
        $limit = max(1, min(100, $limit));

        [$where, $params] = $this->buildWhere($userId, $search, $classId);

        $total = (int) $this->db->prepare("SELECT COUNT(*) FROM students {$where}")
            ->execute($params) ? $this->db->prepare("SELECT COUNT(*) FROM students {$where}")
            ->execute($params) : 0;

        // Use explicit prepare+execute to get count
        $cStmt = $this->db->prepare("SELECT COUNT(*) FROM students {$where}");
        $cStmt->execute($params);
        $total = (int) $cStmt->fetchColumn();

        $offset     = ($page - 1) * $limit;
        $totalPages = (int) ceil($total / max(1, $limit));

        $dStmt = $this->db->prepare(
            "SELECT * FROM students {$where} ORDER BY name ASC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) { $dStmt->bindValue($k, $v); }
        $dStmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $dStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $dStmt->execute();

        return [
            'items'      => array_map(fn($r) => Student::fromArray($r), $dStmt->fetchAll()),
            'pagination' => [
                'page'        => $page,
                'limit'       => $limit,
                'total'       => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }

    public function findById(int $id, int $userId): ?Student
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM students WHERE id = :id AND user_id = :uid LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ? Student::fromArray($row) : null;
    }

    public function nipExistsForUser(string $nip, int $userId, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM students WHERE nip = :nip AND user_id = :uid AND id != :ex'
            );
            $stmt->execute([':nip' => $nip, ':uid' => $userId, ':ex' => $excludeId]);
        } else {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM students WHERE nip = :nip AND user_id = :uid'
            );
            $stmt->execute([':nip' => $nip, ':uid' => $userId]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(int $userId, string $nip, string $name, int $classId): Student
    {
        $stmt = $this->db->prepare(
            'INSERT INTO students (user_id, nip, name, class_id) VALUES (:uid, :nip, :name, :class)'
        );
        $stmt->execute([':uid' => $userId, ':nip' => $nip, ':name' => $name, ':class' => $classId]);
        return $this->findById((int) $this->db->lastInsertId(), $userId);
    }

    public function update(int $id, int $userId, string $nip, string $name, int $classId): ?Student
    {
        $stmt = $this->db->prepare(
            'UPDATE students SET nip = :nip, name = :name, class_id = :class WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([':nip' => $nip, ':name' => $name, ':class' => $classId, ':id' => $id, ':uid' => $userId]);
        return $this->findById($id, $userId);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM students WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function listForAttendance(int $userId, ?int $classId = null): array
    {
        [$where, $params] = $this->buildWhere($userId, null, $classId);
        $stmt = $this->db->prepare("SELECT * FROM students {$where} ORDER BY name ASC");
        $stmt->execute($params);
        return array_map(fn($r) => Student::fromArray($r), $stmt->fetchAll());
    }

    private function buildWhere(int $userId, ?string $search, ?int $classId): array
    {
        $conditions = ['user_id = :uid'];
        $params     = [':uid' => $userId];

        if ($search !== null && $search !== '') {
            $conditions[] = '(nip LIKE :search_nip OR name LIKE :search_name)';
            $params[':search_nip']  = '%' . $search . '%';
            $params[':search_name'] = '%' . $search . '%';
        }
        if ($classId !== null) {
            $conditions[] = 'class_id = :class_id';
            $params[':class_id'] = $classId;
        }

        return ['WHERE ' . implode(' AND ', $conditions), $params];
    }
}
