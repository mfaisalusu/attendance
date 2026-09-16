<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\UseCases\Material\CreateMaterialUseCase;
use App\Application\UseCases\Material\DeleteMaterialUseCase;
use App\Application\UseCases\Material\ListMaterialsUseCase;
use App\Infrastructure\Repositories\CourseMaterialRepository;
use App\Infrastructure\Repositories\DatabaseMasterRepository;
use App\Presentation\Requests\Request;
use App\Presentation\Requests\Validator;
use App\Presentation\Responses\JsonResponse;
use RuntimeException;
use Throwable;

class MaterialController extends BaseController
{
    private const ALLOWED_MIME_TYPES = [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    // GET /api/materials
    public function index(Request $request): void
    {
        $userId   = $this->authUserId();
        $courseId = $request->query('course_id') !== null ? (int) $request->query('course_id') : null;
        $page     = $request->queryInt('page', 1);
        $limit    = $request->queryInt('limit', 20);

        try {
            $result = (new ListMaterialsUseCase(new CourseMaterialRepository()))
                ->execute($userId, $courseId, $page, $limit);
            JsonResponse::success($result);
        } catch (Throwable) { JsonResponse::error('Gagal memuat data materi.', 500); }
    }

    // POST /api/materials
    public function store(Request $request): void
    {
        $userId = $this->authUserId();

        // Validate required fields
        $v = new Validator();
        if (!$v->validate($request->all(), [
            'course_id'    => 'required|integer',
            'meeting_name' => 'required|max:150',
        ])) {
            JsonResponse::unprocessable($v->errors());
            return;
        }

        // Validate file upload
        if (!$request->hasFile('file')) {
            JsonResponse::unprocessable(['file' => ['File harus diupload.']]);
            return;
        }

        $file = $request->file('file');

        // Validate file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            JsonResponse::unprocessable(['file' => ['Ukuran file maksimal 10MB.']]);
            return;
        }

        // Validate file type (docx only)
        $mimeType = $file['type'] ?? '';
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            JsonResponse::unprocessable(['file' => ['Format harus .docx (Word).']]);
            return;
        }

        $courseId    = (int) $request->input('course_id');
        $meetingName = $request->input('meeting_name');
        $originalName = $file['name'];

        try {
            // Create storage directory if not exists
            $storageDir = dirname(__DIR__, 3) . '/storage/materials';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $uniqueName = uniqid('material_', true) . '.' . $extension;
            $targetPath = $storageDir . '/' . $uniqueName;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new RuntimeException('Gagal menyimpan file.');
            }

            $material = (new CreateMaterialUseCase(
                new CourseMaterialRepository(),
                new DatabaseMasterRepository()
            ))->execute(
                $userId,
                $courseId,
                $meetingName,
                $uniqueName,
                $originalName,
                (int) $file['size'],
                $mimeType
            );

            JsonResponse::created($material->toArray(), 'Materi berhasil ditambahkan.');
        } catch (\InvalidArgumentException $e) {
            JsonResponse::unprocessable(['general' => [$e->getMessage()]]);
        } catch (Throwable $e) {
            JsonResponse::error('Gagal menambahkan materi.', 500);
        }
    }

    // DELETE /api/materials/{id}
    public function destroy(Request $request): void
    {
        $userId      = $this->authUserId();
        $materialId = (int) $request->param('id');

        try {
            (new DeleteMaterialUseCase(new CourseMaterialRepository()))
                ->execute($materialId, $userId);
            JsonResponse::success(null, 'Materi berhasil dihapus.');
        } catch (RuntimeException $e) {
            JsonResponse::notFound($e->getMessage());
        } catch (Throwable) {
            JsonResponse::error('Gagal menghapus materi.', 500);
        }
    }

    // GET /api/materials/download/{id}
    public function download(Request $request): void
    {
        $userId     = $this->authUserId();
        $materialId = (int) $request->param('id');

        $repository = new CourseMaterialRepository();
        $material = $repository->findById($materialId, $userId);

        if (!$material) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Materi tidak ditemukan.']);
            return;
        }

        $filePath = dirname(__DIR__, 3) . '/storage/materials/' . $material->filePath;

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'File tidak ditemukan.']);
            return;
        }

        // Set headers for file download
        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($material->mimeType ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $material->fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Pragma: public');

        readfile($filePath);
        exit;
    }

    // GET /api/materials/view/{id}
    public function view(Request $request): void
    {
        $userId     = $this->authUserId();
        $materialId = (int) $request->param('id');

        $repository = new CourseMaterialRepository();
        $material = $repository->findById($materialId, $userId);

        if (!$material) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Materi tidak ditemukan.']);
            return;
        }

        $filePath = dirname(__DIR__, 3) . '/storage/materials/' . $material->filePath;

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'File tidak ditemukan.']);
            return;
        }

        // Set headers for inline viewing (display in browser)
        header('Content-Type: ' . ($material->mimeType ?? 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . $material->fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=3600');

        readfile($filePath);
        exit;
    }

    // GET /api/materials/content/{id}
    public function content(Request $request): void
    {
        $userId     = $this->authUserId();
        $materialId = (int) $request->param('id');

        $repository = new CourseMaterialRepository();
        $material = $repository->findById($materialId, $userId);

        if (!$material) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Materi tidak ditemukan.']);
            return;
        }

        $filePath = dirname(__DIR__, 3) . '/storage/materials/' . $material->filePath;

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'File tidak ditemukan.']);
            return;
        }

        // Return raw binary so the frontend can parse it with mammoth.js
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: inline; filename="' . $material->fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=3600');
        header('Access-Control-Expose-Headers: Content-Disposition');

        readfile($filePath);
        exit;
    }
}