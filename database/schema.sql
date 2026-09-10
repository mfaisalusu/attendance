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
-- Setiap mata kuliah milik satu jurusan.
-- Kode digenerate otomatis: {dept_code}-{inisial_nama}
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL,
    `department_id` INT UNSIGNED    NOT NULL,
    `code`          VARCHAR(20)     NOT NULL,
    `name`          VARCHAR(150)    NOT NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_course_user_id`  (`user_id`),
    KEY `idx_course_dept_id`  (`department_id`),
    CONSTRAINT `fk_course_user` FOREIGN KEY (`user_id`)       REFERENCES `users`       (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_course_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: classes (Kelas)
-- Setiap kelas milik satu jurusan, satu semester, satu tahun.
-- Kode digenerate otomatis: {dept_code}-SEM-{semester}-{year}
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `classes` (
    `id`            INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED        NOT NULL,
    `department_id` INT UNSIGNED        NOT NULL,
    `code`          VARCHAR(30)         NOT NULL,
    `name`          VARCHAR(100)        NOT NULL,
    `semester_id`   TINYINT UNSIGNED    NOT NULL COMMENT '1–8',
    `year`          SMALLINT UNSIGNED   NOT NULL COMMENT 'Tahun akademik, mulai 2026',
    `created_at`    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_class_user_id`   (`user_id`),
    KEY `idx_class_dept_id`   (`department_id`),
    KEY `idx_class_semester`  (`semester_id`),
    KEY `idx_class_year`      (`year`),
    CONSTRAINT `fk_class_user`      FOREIGN KEY (`user_id`)       REFERENCES `users`       (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_class_dept`      FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `chk_semester_range` CHECK (`semester_id` BETWEEN 1 AND 8),
    CONSTRAINT `chk_year_min`       CHECK (`year` >= 2026)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: class_courses (Pivot — Kelas ↔ Mata Kuliah, many-to-many)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `class_courses` (
    `class_id`  INT UNSIGNED NOT NULL,
    `course_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`class_id`, `course_id`),
    KEY `idx_cc_course_id` (`course_id`),
    CONSTRAINT `fk_cc_class`  FOREIGN KEY (`class_id`)  REFERENCES `classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cc_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: students
-- Mahasiswa hanya terikat ke kelas (class_id).
-- Jurusan, mata kuliah, dan semester diambil dari kelas.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED    NOT NULL,
    `nip`        VARCHAR(30)     NOT NULL,
    `name`       VARCHAR(100)    NOT NULL,
    `class_id`   INT UNSIGNED    NOT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_students_user_nip` (`user_id`, `nip`),
    KEY `idx_students_user_id` (`user_id`),
    KEY `idx_students_nip`     (`nip`),
    KEY `idx_students_class`   (`class_id`),
    CONSTRAINT `fk_students_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`   (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_students_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: attendance
-- Unik per (student, tanggal, mata kuliah) — satu mahasiswa bisa
-- punya status berbeda untuk mata kuliah berbeda di hari yang sama.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `student_id`      INT UNSIGNED    NOT NULL,
    `course_id`       INT UNSIGNED    NOT NULL,
    `attendance_date` DATE            NOT NULL,
    `status`          ENUM('hadir','izin','sakit','alpha') NOT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_attendance_student_course_date` (`student_id`, `course_id`, `attendance_date`),
    KEY `idx_attendance_student_id` (`student_id`),
    KEY `idx_attendance_course_id`  (`course_id`),
    KEY `idx_attendance_date`       (`attendance_date`),
    CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_attendance_course`  FOREIGN KEY (`course_id`)  REFERENCES `courses`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
