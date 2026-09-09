<?php

declare(strict_types=1);

namespace App\Presentation\Responses;

class JsonResponse
{
    public static function success(
        mixed  $data    = null,
        string $message = 'OK',
        int    $status  = 200
    ): never {
        self::send([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function created(
        mixed  $data    = null,
        string $message = 'Data berhasil dibuat'
    ): never {
        self::success($data, $message, 201);
    }

    public static function error(
        string $message = 'Terjadi kesalahan',
        int    $status  = 400,
        array  $errors  = []
    ): never {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $body['errors'] = $errors;
        }

        self::send($body, $status);
    }

    public static function unauthorized(string $message = 'Unauthorized'): never
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): never
    {
        self::error($message, 403);
    }

    public static function notFound(string $message = 'Data tidak ditemukan'): never
    {
        self::error($message, 404);
    }

    public static function unprocessable(array $errors, string $message = 'Data tidak valid'): never
    {
        self::error($message, 422, $errors);
    }

    // ------------------------------------------------------------------

    private static function send(array $body, int $status): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
