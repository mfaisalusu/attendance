<?php

declare(strict_types=1);

use App\Presentation\Middleware\AuthMiddleware;

$auth = [AuthMiddleware::handle()];

// ----------------------------------------------------------------
// Auth routes (public)
// ----------------------------------------------------------------
$router->post('/api/auth/register',   [new App\Presentation\Controllers\AuthController(), 'register']);
$router->post('/api/auth/login',      [new App\Presentation\Controllers\AuthController(), 'login']);
$router->post('/api/auth/verify-2fa', [new App\Presentation\Controllers\AuthController(), 'verify2fa']);
$router->post('/api/auth/logout',     [new App\Presentation\Controllers\AuthController(), 'logout'],    $auth);
$router->get( '/api/auth/me',         [new App\Presentation\Controllers\AuthController(), 'me'],        $auth);

// ----------------------------------------------------------------
// Master data routes (protected)
// ----------------------------------------------------------------
$masterController = new App\Presentation\Controllers\MasterController();

$router->get('/api/master/departments', [$masterController, 'departments'], $auth);
$router->get('/api/master/courses',     [$masterController, 'courses'],     $auth);
$router->get('/api/master/classes',     [$masterController, 'classes'],     $auth);
$router->get('/api/master/semesters',   [$masterController, 'semesters'],   $auth);

// ----------------------------------------------------------------
// Student routes (protected)
// ----------------------------------------------------------------
$studentController = new App\Presentation\Controllers\StudentController();

$router->get(   '/api/students',      [$studentController, 'index'],   $auth);
$router->post(  '/api/students',      [$studentController, 'store'],   $auth);
$router->get(   '/api/students/{id}', [$studentController, 'show'],    $auth);
$router->put(   '/api/students/{id}', [$studentController, 'update'],  $auth);
$router->delete('/api/students/{id}', [$studentController, 'destroy'], $auth);

// ----------------------------------------------------------------
// Attendance routes (protected)
// NOTE: literal routes must be registered before parameterised ones
// ----------------------------------------------------------------
$attendanceController = new App\Presentation\Controllers\AttendanceController();

$router->get( '/api/attendance/recap', [$attendanceController, 'recap'],  $auth);  // must come first
$router->get( '/api/attendance',       [$attendanceController, 'index'],  $auth);
$router->post('/api/attendance',       [$attendanceController, 'store'],  $auth);
$router->put( '/api/attendance/{id}',  [$attendanceController, 'update'], $auth);

// ----------------------------------------------------------------
// Dashboard route (protected)
// ----------------------------------------------------------------
$router->get('/api/dashboard', [new App\Presentation\Controllers\DashboardController(), 'index'], $auth);
