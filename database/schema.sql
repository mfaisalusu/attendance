-- ============================================================
-- Attendance App Database Schema
-- MySQL 8+ / InnoDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS `attendance_app`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `attendance_app`;

-- ------------------------------------------------------------
-- Table: users (Dosen)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`              VARCHAR(100)    NOT NULL,
    `email`             VARCHAR(150)    NOT NULL,
    `password`          VARCHAR(255)    NOT NULL,
    `email_verified_at` DATETIME        NULL DEFAULT NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: otp_tokens
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `otp_tokens` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED    NOT NULL,
    `token_hash` VARCHAR(255)    NOT NULL,
    `expires_at` DATETIME        NOT NULL,
    `attempts`   TINYINT         NOT NULL DEFAULT 0,
    `used_at`    DATETIME        NULL DEFAULT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_otp_user_id` (`user_id`),
    CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Master Data Tables
-- Setiap tabel master terikat ke user (dosen) pemiliknya.
-- Semester tidak memerlukan tabel — nilainya fixed 1–8.
-- ============================================================

-- ------------------------------------------------------------
-- Table: departments (Jurusan)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `departments` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED    NOT NULL,
    `code`       VARCHAR(20)     NOT NULL,
    `name`       VARCHAR(100)    NOT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_dept_user_id` (`user_id`),
    CONSTRAINT `fk_dept_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: courses (Mata Kuliah)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED    NOT NULL,
    `code`       VARCHAR(20)     NOT NULL,
    `name`       VARCHAR(150)    NOT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_course_user_id` (`user_id`),
    CONSTRAINT `fk_course_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: classes (Kelas)
-- Menyimpan semester_id (1–8) dan tahun akademik.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `classes` (
    `id`          INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED        NOT NULL,
    `code`        VARCHAR(20)         NOT NULL,
    `name`        VARCHAR(100)        NOT NULL,
    `semester_id` TINYINT UNSIGNED    NOT NULL COMMENT '1–8',
    `year`        SMALLINT UNSIGNED   NOT NULL COMMENT 'Tahun akademik, mulai 2026',
    `created_at`  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_class_user_id`   (`user_id`),
    KEY `idx_class_semester`  (`semester_id`),
    KEY `idx_class_year`      (`year`),
    CONSTRAINT `fk_class_user`     FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_semester_range` CHECK (`semester_id` BETWEEN 1 AND 8),
    CONSTRAINT `chk_year_min`       CHECK (`year` >= 2026)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: students
-- department_id, course_id, class_id sekarang merujuk ke tabel
-- master di atas. semester_id tetap integer langsung (1–8).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
    `id`            INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED        NOT NULL,
    `nip`           VARCHAR(30)         NOT NULL,
    `name`          VARCHAR(100)        NOT NULL,
    `department_id` INT UNSIGNED        NOT NULL,
    `course_id`     INT UNSIGNED        NOT NULL,
    `class_id`      INT UNSIGNED        NOT NULL,
    `semester_id`   TINYINT UNSIGNED    NOT NULL COMMENT '1–8',
    `created_at`    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_students_user_nip` (`user_id`, `nip`),
    KEY `idx_students_user_id`  (`user_id`),
    KEY `idx_students_nip`      (`nip`),
    KEY `idx_students_dept`     (`department_id`),
    KEY `idx_students_course`   (`course_id`),
    KEY `idx_students_class`    (`class_id`),
    KEY `idx_students_semester` (`semester_id`),
    CONSTRAINT `fk_students_user`   FOREIGN KEY (`user_id`)       REFERENCES `users`       (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_students_dept`   FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_students_course` FOREIGN KEY (`course_id`)     REFERENCES `courses`     (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_students_class`  FOREIGN KEY (`class_id`)      REFERENCES `classes`     (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: attendance
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `student_id`      INT UNSIGNED    NOT NULL,
    `attendance_date` DATE            NOT NULL,
    `status`          ENUM('hadir','izin','sakit','alpha') NOT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_attendance_student_date` (`student_id`, `attendance_date`),
    KEY `idx_attendance_student_id` (`student_id`),
    KEY `idx_attendance_date`       (`attendance_date`),
    CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
