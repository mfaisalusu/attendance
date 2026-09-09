# Aplikasi Absensi Mahasiswa

Aplikasi web untuk pencatatan dan rekap absensi mahasiswa. Dosen dapat login, mengelola data mahasiswa, mencatat kehadiran harian, dan melihat rekap absensi bulanan.

---

## Tech Stack

| Layer     | Teknologi                                   |
|-----------|---------------------------------------------|
| Frontend  | Svelte 4, Vite 5, Vanilla CSS               |
| Backend   | Native PHP 8.1+, Clean Architecture         |
| Database  | MySQL 8+                                    |
| Auth      | JWT (firebase/php-jwt v7), 2FA via OTP Email|
| Mail      | PHPMailer 6 (SMTP)                          |

---

## Struktur Proyek

```
attendance/
├── backend/                  ← Native PHP REST API
│   ├── public/
│   │   └── index.php         ← Entry point
│   ├── src/
│   │   ├── Domain/           ← Entities, Repositories (interfaces), ValueObjects
│   │   ├── Application/      ← UseCases, DTO, Services
│   │   ├── Infrastructure/   ← Database, Repositories (impl), Mail, Security
│   │   └── Presentation/     ← Controllers, Middleware, Router, Requests, Responses
│   ├── config/               ← app.php, database.php, mail.php
│   ├── routes/
│   │   └── api.php           ← Route definitions
│   ├── storage/
│   │   └── master/           ← departments.json, courses.json, classes.json, semesters.json
│   ├── composer.json
│   └── .env.example
│
├── frontend/                 ← Svelte SPA
│   ├── src/
│   │   ├── core/             ← HTTP client, auth store, constants, utils
│   │   ├── domain/           ← (reserved for domain models)
│   │   ├── application/      ← services (authService, masterService)
│   │   ├── infrastructure/   ← API adapters (authApi, studentApi, etc.)
│   │   ├── presentation/     ← components, layouts, pages
│   │   └── routes/           ← Router.svelte (SPA router + auth guard)
│   ├── index.html
│   ├── vite.config.js
│   └── package.json
│
└── database/
    └── schema.sql            ← Database schema siap import
```

---

## Requirements

### Backend
- PHP 8.1+
- MySQL 8+
- Composer
- PHP extensions: `pdo_mysql`, `mbstring`, `openssl`

### Frontend
- Node.js 18+ (direkomendasikan Node 20 LTS)
- npm 9+

---

## Instalasi

### 1. Clone / Extract Project

```bash
cd /path/to/attendance
```

### 2. Database

Buat database dan import schema:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS attendance_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p attendance_app < database/schema.sql
```

### 3. Backend

#### Install dependencies

```bash
cd backend
composer install
```

#### Konfigurasi environment

```bash
cp .env.example .env
```

Edit `.env` sesuai kebutuhan (lihat bagian [Konfigurasi](#konfigurasi) di bawah).

#### Jalankan backend

**Development (PHP built-in server):**

```bash
cd backend
php -S localhost:8000 -t public
```

Atau jika menggunakan Apache/Nginx, arahkan document root ke `backend/public/` dan pastikan `.htaccess` aktif (mod_rewrite).

### 4. Frontend

```bash
cd frontend
npm install
```

**Development:**

```bash
npm run dev
```

Frontend berjalan di `http://localhost:5173` dan melakukan proxy `/api` ke backend di `http://localhost:8000`.

**Production build:**

```bash
npm run build
```

Output ada di `frontend/dist/`. Deploy ke web server manapun.

---

## Konfigurasi

### Backend `.env`

```env
# Aplikasi
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=attendance_app
DB_USERNAME=root
DB_PASSWORD=

# Email SMTP (untuk OTP 2FA)
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@attendance.dev
MAIL_FROM_NAME="Attendance App"

# JWT
JWT_SECRET=ganti_dengan_string_random_minimal_32_karakter

# CORS (sesuaikan dengan URL frontend)
CORS_ALLOWED_ORIGINS=http://localhost:5173
```

### Konfigurasi SMTP

Aplikasi menggunakan PHPMailer dengan SMTP. Beberapa opsi:

| Provider       | Host                  | Port | Note                          |
|----------------|-----------------------|------|-------------------------------|
| Mailtrap       | sandbox.smtp.mailtrap.io | 2525 | Development/testing           |
| Gmail          | smtp.gmail.com        | 587  | Butuh App Password            |
| SendGrid       | smtp.sendgrid.net     | 587  | Butuh API key sebagai password|
| Mailgun        | smtp.mailgun.org      | 587  | Butuh credentials mailgun     |

### JWT Secret

Generate string random yang kuat:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Salin output ke `JWT_SECRET` di `.env`.

---

## Cara Menjalankan (Development)

**Terminal 1 — Backend:**

```bash
cd backend
php -S localhost:8000 -t public
```

**Terminal 2 — Frontend:**

```bash
cd frontend
npm run dev
```

Buka browser: `http://localhost:5173`

---

## Default Development

- Backend URL: `http://localhost:8000`
- Frontend URL: `http://localhost:5173`
- Database: `attendance_app` di MySQL localhost
- JWT TTL: 24 jam
- OTP TTL: 5 menit
- OTP max attempts: 5

---

## API Documentation

### Base URL

```
http://localhost:8000/api
```

### Response Format

**Success:**
```json
{
  "success": true,
  "message": "OK",
  "data": {}
}
```

**Error:**
```json
{
  "success": false,
  "message": "Pesan error",
  "errors": {
    "field": ["detail error"]
  }
}
```

### HTTP Status Codes

| Code | Arti                  |
|------|-----------------------|
| 200  | OK                    |
| 201  | Created               |
| 400  | Bad Request           |
| 401  | Unauthorized          |
| 403  | Forbidden             |
| 404  | Not Found             |
| 422  | Unprocessable Entity  |
| 500  | Internal Server Error |

---

### Authentication

> Semua endpoint kecuali register, login, dan verify-2fa membutuhkan header:
> ```
> Authorization: Bearer <token>
> ```

---

#### POST `/api/auth/register`

Daftar akun dosen baru.

**Request Body:**
```json
{
  "name": "Dr. Budi Santoso",
  "email": "budi@kampus.ac.id",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response `201`:**
```json
{
  "success": true,
  "message": "Akun berhasil dibuat. Silakan login.",
  "data": {
    "id": 1,
    "name": "Dr. Budi Santoso",
    "email": "budi@kampus.ac.id",
    "email_verified_at": null,
    "created_at": "2026-09-09 10:00:00"
  }
}
```

---

#### POST `/api/auth/login`

Login dengan email dan password. OTP akan dikirim ke email.

**Request Body:**
```json
{
  "email": "budi@kampus.ac.id",
  "password": "password123"
}
```

**Response `200`:**
```json
{
  "success": true,
  "message": "Kode verifikasi telah dikirim ke email.",
  "data": {
    "requires_2fa": true
  }
}
```

---

#### POST `/api/auth/verify-2fa`

Verifikasi OTP 6 digit yang dikirim via email.

**Request Body:**
```json
{
  "email": "budi@kampus.ac.id",
  "token": "123456"
}
```

**Response `200`:**
```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "authenticated": true,
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "id": 1,
      "name": "Dr. Budi Santoso",
      "email": "budi@kampus.ac.id"
    }
  }
}
```

---

#### POST `/api/auth/logout` 🔒

Logout (JWT stateless — client harus membuang token).

**Response `200`:**
```json
{
  "success": true,
  "message": "Logout berhasil.",
  "data": null
}
```

---

#### GET `/api/auth/me` 🔒

Data dosen yang sedang login.

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": 1,
    "name": "Dr. Budi Santoso",
    "email": "budi@kampus.ac.id",
    "email_verified_at": "2026-09-09 10:05:00",
    "created_at": "2026-09-09 10:00:00"
  }
}
```

---

### Master Data

> Semua endpoint master data membutuhkan autentikasi 🔒

---

#### GET `/api/master/departments` 🔒

```json
{
  "success": true,
  "data": [
    { "id": 1, "code": "TI", "name": "Teknik Informatika" },
    { "id": 2, "code": "SI", "name": "Sistem Informasi" }
  ]
}
```

#### GET `/api/master/courses` 🔒

```json
{
  "success": true,
  "data": [
    { "id": 1, "code": "IF101", "name": "Pemrograman Web" },
    { "id": 2, "code": "IF102", "name": "Basis Data" }
  ]
}
```

#### GET `/api/master/classes` 🔒

```json
{
  "success": true,
  "data": [
    { "id": 1, "code": "TI-A", "name": "Teknik Informatika A" }
  ]
}
```

#### GET `/api/master/semesters` 🔒

```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "Semester 1" },
    { "id": 2, "name": "Semester 2" }
  ]
}
```

---

### Students

> Semua endpoint mahasiswa membutuhkan autentikasi 🔒
> Data mahasiswa terisolasi per dosen (ownership via `user_id`).

---

#### GET `/api/students` 🔒

List mahasiswa dengan pagination, search, dan filter.

**Query Parameters:**

| Parameter     | Tipe    | Keterangan                            |
|---------------|---------|---------------------------------------|
| `page`        | integer | Halaman (default: 1)                  |
| `limit`       | integer | Per halaman, max 100 (default: 20)    |
| `search`      | string  | Cari berdasarkan NIP atau nama        |
| `department_id` | integer | Filter jurusan                      |
| `course_id`   | integer | Filter mata kuliah                    |
| `class_id`    | integer | Filter kelas                          |
| `semester_id` | integer | Filter semester                       |

**Response `200`:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "user_id": 1,
        "nip": "2021001",
        "name": "Budi Santoso",
        "department_id": 1,
        "course_id": 1,
        "class_id": 1,
        "semester_id": 3,
        "department": { "id": 1, "code": "TI", "name": "Teknik Informatika" },
        "course":     { "id": 1, "code": "IF101", "name": "Pemrograman Web" },
        "class":      { "id": 1, "code": "TI-A", "name": "Teknik Informatika A" },
        "semester":   { "id": 3, "name": "Semester 3" },
        "created_at": "2026-09-09 10:00:00",
        "updated_at": "2026-09-09 10:00:00"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 1,
      "total_pages": 1
    }
  }
}
```

---

#### GET `/api/students/{id}` 🔒

Detail satu mahasiswa.

**Response `200`:** sama seperti item di atas, langsung di `data`.

---

#### POST `/api/students` 🔒

Tambah mahasiswa baru.

**Request Body:**
```json
{
  "nip": "2021001",
  "name": "Budi Santoso",
  "department_id": 1,
  "course_id": 1,
  "class_id": 1,
  "semester_id": 3
}
```

**Response `201`:**
```json
{
  "success": true,
  "message": "Mahasiswa berhasil ditambahkan.",
  "data": { ... }
}
```

---

#### PUT `/api/students/{id}` 🔒

Update data mahasiswa.

**Request Body:** sama seperti POST.

**Response `200`:**
```json
{
  "success": true,
  "message": "Data mahasiswa berhasil diperbarui.",
  "data": { ... }
}
```

---

#### DELETE `/api/students/{id}` 🔒

Hapus mahasiswa (cascade: data absensi juga terhapus).

**Response `200`:**
```json
{
  "success": true,
  "message": "Mahasiswa berhasil dihapus.",
  "data": null
}
```

---

### Attendance

> Semua endpoint absensi membutuhkan autentikasi 🔒

---

#### GET `/api/attendance` 🔒

Ambil daftar mahasiswa + status absensi untuk tanggal tertentu.

**Query Parameters:**

| Parameter     | Tipe   | Keterangan                       |
|---------------|--------|----------------------------------|
| `date`        | string | Format YYYY-MM-DD (default: hari ini) |
| `department_id` | integer | Filter jurusan               |
| `course_id`   | integer | Filter mata kuliah               |
| `class_id`    | integer | Filter kelas                     |
| `semester_id` | integer | Filter semester                  |

**Response `200`:**
```json
{
  "success": true,
  "data": {
    "date": "2026-09-09",
    "students": [
      {
        "id": 1,
        "nip": "2021001",
        "name": "Budi Santoso",
        "attendance": {
          "id": 5,
          "status": "hadir"
        }
      },
      {
        "id": 2,
        "nip": "2021002",
        "name": "Andi Wijaya",
        "attendance": null
      }
    ],
    "summary": {
      "total": 30,
      "sudah_diabsen": 28,
      "belum_diabsen": 2
    }
  }
}
```

---

#### POST `/api/attendance` 🔒

Simpan absensi harian (bulk — seluruh kelas sekaligus). Menggunakan database transaction.

**Request Body:**
```json
{
  "date": "2026-09-09",
  "attendance": [
    { "student_id": 1, "status": "hadir" },
    { "student_id": 2, "status": "izin" },
    { "student_id": 3, "status": "alpha" }
  ]
}
```

> `status` yang valid: `hadir`, `izin`, `sakit`, `alpha`

> Jika absensi sudah ada untuk tanggal tersebut, akan diupdate (upsert).

**Response `200`:**
```json
{
  "success": true,
  "message": "Absensi berhasil disimpan.",
  "data": null
}
```

---

#### PUT `/api/attendance/{id}` 🔒

Update status satu record absensi.

**Request Body:**
```json
{
  "status": "sakit"
}
```

**Response `200`:**
```json
{
  "success": true,
  "message": "Absensi berhasil diperbarui.",
  "data": {
    "id": 5,
    "student_id": 1,
    "attendance_date": "2026-09-09",
    "status": "sakit",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

---

#### GET `/api/attendance/recap` 🔒

Rekap absensi bulanan per mahasiswa (agregasi SQL).

**Query Parameters:**

| Parameter     | Tipe    | Keterangan                     |
|---------------|---------|-------------------------------|
| `year`        | integer | **Wajib.** Tahun (cth: 2026)  |
| `month`       | integer | **Wajib.** Bulan 1–12         |
| `department_id` | integer | Filter jurusan               |
| `course_id`   | integer | Filter mata kuliah             |
| `class_id`    | integer | Filter kelas                   |
| `semester_id` | integer | Filter semester                |

**Response `200`:**
```json
{
  "success": true,
  "data": {
    "year": 2026,
    "month": 9,
    "summary": {
      "total_mahasiswa": 30,
      "total_hadir": 540,
      "total_izin": 15,
      "total_sakit": 10,
      "total_alpha": 5
    },
    "rows": [
      {
        "student_id": 1,
        "nip": "2021001",
        "name": "Budi Santoso",
        "hadir": 18,
        "izin": 1,
        "sakit": 0,
        "alpha": 1,
        "total_pertemuan": 20,
        "persentase": 90.00
      }
    ]
  }
}
```

---

### Dashboard

#### GET `/api/dashboard` 🔒

Statistik ringkas untuk dosen yang sedang login.

**Response `200`:**
```json
{
  "success": true,
  "data": {
    "total_mahasiswa": 45,
    "today": "2026-09-09",
    "total_absen_hari_ini": 30,
    "hadir_hari_ini": 27,
    "izin_hari_ini": 2,
    "sakit_hari_ini": 1,
    "alpha_hari_ini": 0,
    "shortcuts": [
      { "label": "Tambah Mahasiswa",  "path": "/students/create" },
      { "label": "Absensi Hari Ini",  "path": "/attendance" },
      { "label": "Rekap Absensi",     "path": "/attendance/recap" }
    ]
  }
}
```

---

## Frontend Routes

| Path                  | Keterangan                        | Auth |
|-----------------------|-----------------------------------|------|
| `/login`              | Halaman login                     | ✗    |
| `/register`           | Halaman daftar akun               | ✗    |
| `/verify-2fa`         | Verifikasi OTP                    | ✗    |
| `/dashboard`          | Dashboard utama                   | ✓    |
| `/students`           | List mahasiswa + CRUD             | ✓    |
| `/attendance`         | Absensi harian                    | ✓    |
| `/attendance/recap`   | Rekap absensi bulanan             | ✓    |

---

## Keamanan

- Password di-hash menggunakan `password_hash()` (bcrypt)
- OTP di-hash menggunakan bcrypt sebelum disimpan
- JWT HS256 dengan secret minimal 32 karakter
- JWT TTL: 24 jam
- OTP TTL: 5 menit, max 5 percobaan
- Login tidak membocorkan apakah email terdaftar (timing-safe)
- Semua query menggunakan PDO prepared statements
- Ownership isolation: dosen hanya bisa akses data mahasiswanya sendiri
- CORS dikonfigurasi via environment variable

---

## Definition of Done Checklist

- [x] Dosen dapat register menggunakan email
- [x] Dosen dapat login menggunakan email + password
- [x] Sistem mengirim OTP 2FA melalui email
- [x] Dosen dapat melakukan verifikasi OTP
- [x] Dosen dapat logout
- [x] Dosen dapat melihat dashboard
- [x] Dosen dapat menambah mahasiswa
- [x] Dosen dapat mengedit mahasiswa
- [x] Dosen dapat menghapus mahasiswa
- [x] Dosen dapat mencari mahasiswa (NIP / nama)
- [x] Dosen dapat memfilter mahasiswa (jurusan/matkul/kelas/semester)
- [x] Master jurusan tersedia via API
- [x] Master mata kuliah tersedia via API
- [x] Master kelas tersedia via API
- [x] Master semester 1-8 tersedia via API
- [x] Dosen dapat memilih tanggal absensi
- [x] Dosen dapat mengisi kehadiran dengan radio button
- [x] Status hadir, izin, sakit, alpha tersedia
- [x] Tombol "Semua Hadir" tersedia
- [x] Dosen dapat menyimpan absensi (bulk, dengan transaction)
- [x] Sistem mencegah duplikasi absensi (UNIQUE constraint + upsert)
- [x] Dosen dapat melihat rekap bulanan
- [x] Rekap menampilkan Hadir, Izin, Sakit, Alpha, persentase kehadiran
- [x] Data dosen terisolasi berdasarkan ownership
- [x] API menggunakan authentication middleware
- [x] Password menggunakan password_hash
- [x] SQL menggunakan PDO prepared statements
- [x] Environment menggunakan .env
- [x] Tidak ada password/token hardcode
- [x] Tidak menggunakan framework PHP
- [x] Backend menggunakan Clean Architecture
- [x] Frontend menggunakan Clean Architecture
