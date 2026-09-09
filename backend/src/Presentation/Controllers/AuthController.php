<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\DTO\LoginDTO;
use App\Application\DTO\RegisterDTO;
use App\Application\DTO\Verify2faDTO;
use App\Application\Services\OtpService;
use App\Application\UseCases\Auth\LoginUseCase;
use App\Application\UseCases\Auth\RegisterUseCase;
use App\Application\UseCases\Auth\Verify2faUseCase;
use App\Infrastructure\Mail\SmtpMailer;
use App\Infrastructure\Repositories\OtpTokenRepository;
use App\Infrastructure\Repositories\UserRepository;
use App\Presentation\Requests\Request;
use App\Presentation\Requests\Validator;
use App\Presentation\Responses\JsonResponse;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AuthController extends BaseController
{
    // ------------------------------------------------------------------
    // POST /api/auth/register
    // ------------------------------------------------------------------
    public function register(Request $request): void
    {
        $data      = $request->all();
        $validator = new Validator();

        $valid = $validator->validate($data, [
            'name'                  => 'required|max:100',
            'email'                 => 'required|email|max:150',
            'password'              => 'required|min:8|max:255',
            'password_confirmation' => 'required|same:password',
        ]);

        if (!$valid) {
            JsonResponse::unprocessable($validator->errors());
        }

        try {
            $useCase = new RegisterUseCase(new UserRepository());
            $user    = $useCase->execute(new RegisterDTO(
                name:                 $data['name'],
                email:                $data['email'],
                password:             $data['password'],
                passwordConfirmation: $data['password_confirmation'],
            ));

            JsonResponse::created(
                $user->toPublicArray(),
                'Akun berhasil dibuat. Silakan login.'
            );
        } catch (InvalidArgumentException $e) {
            JsonResponse::unprocessable(['email' => [$e->getMessage()]]);
        } catch (Throwable) {
            JsonResponse::error('Gagal membuat akun. Coba lagi nanti.', 500);
        }
    }

    // ------------------------------------------------------------------
    // POST /api/auth/login
    // ------------------------------------------------------------------
    public function login(Request $request): void
    {
        $data      = $request->all();
        $validator = new Validator();

        $valid = $validator->validate($data, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!$valid) {
            JsonResponse::unprocessable($validator->errors());
        }

        try {
            $useCase = new LoginUseCase(
                new UserRepository(),
                new OtpTokenRepository(),
                new OtpService(new OtpTokenRepository(), new SmtpMailer()),
            );

            $result = $useCase->execute(new LoginDTO(
                email:    $data['email'],
                password: $data['password'],
            ));

            if ($result['mode'] === 'dev_bypass') {
                // Development: langsung authenticated, tidak perlu OTP
                JsonResponse::success(
                    [
                        'authenticated' => true,
                        'token'         => $result['token'],
                        'user'          => $result['user'],
                    ],
                    'Login berhasil. (development mode — OTP dilewati)'
                );
            }

            // Production: arahkan frontend ke halaman verify-2fa
            JsonResponse::success(
                ['requires_2fa' => true],
                'Kode verifikasi telah dikirim ke email.'
            );
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 401);
        } catch (Throwable $e) {
            JsonResponse::error('Gagal memproses login. Coba lagi nanti.', 500);
        }
    }

    // ------------------------------------------------------------------
    // POST /api/auth/verify-2fa
    // ------------------------------------------------------------------
    public function verify2fa(Request $request): void
    {
        $data      = $request->all();
        $validator = new Validator();

        $valid = $validator->validate($data, [
            'email' => 'required|email',
            'token' => 'required',
        ]);

        if (!$valid) {
            JsonResponse::unprocessable($validator->errors());
        }

        try {
            $useCase = new Verify2faUseCase(
                new UserRepository(),
                new OtpTokenRepository(),
            );

            $jwt = $useCase->execute(new Verify2faDTO(
                email: $data['email'],
                token: $data['token'],
            ));

            // Fetch user to return in response
            $userRepo = new UserRepository();
            $user     = $userRepo->findByEmail($data['email']);

            JsonResponse::success(
                [
                    'authenticated' => true,
                    'token'         => $jwt,
                    'user'          => $user?->toPublicArray(),
                ],
                'Login berhasil.'
            );
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 401);
        } catch (Throwable) {
            JsonResponse::error('Gagal memverifikasi kode. Coba lagi nanti.', 500);
        }
    }

    // ------------------------------------------------------------------
    // POST /api/auth/logout  (protected)
    // ------------------------------------------------------------------
    public function logout(Request $request): void
    {
        // JWT is stateless; client must discard the token.
        // Optionally we could blacklist — out of MVP scope.
        JsonResponse::success(null, 'Logout berhasil.');
    }

    // ------------------------------------------------------------------
    // GET /api/auth/me  (protected)
    // ------------------------------------------------------------------
    public function me(Request $request): void
    {
        $userId   = $this->authUserId();
        $userRepo = new UserRepository();
        $user     = $userRepo->findById($userId);

        if ($user === null) {
            JsonResponse::notFound('User tidak ditemukan.');
        }

        JsonResponse::success($user->toPublicArray());
    }
}
