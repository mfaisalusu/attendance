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

-- ------------------------------------------------------------
-- Table: students
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL,
    `nip`           VARCHAR(30)     NOT NULL,
    `name`          VARCHAR(100)    NOT NULL,
    `department_id` INT UNSIGNED    NOT NULL,
    `course_id`     INT UNSIGNED    NOT NULL,
    `class_id`      INT UNSIGNED    NOT NULL,
    `semester_id`   TINYINT UNSIGNED NOT NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_students_user_nip` (`user_id`, `nip`),
    KEY `idx_students_user_id`   (`user_id`),
    KEY `idx_students_nip`       (`nip`),
    KEY `idx_students_dept`      (`department_id`),
    KEY `idx_students_course`    (`course_id`),
    KEY `idx_students_class`     (`class_id`),
    KEY `idx_students_semester`  (`semester_id`),
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
    KEY `idx_attendance_student_id`   (`student_id`),
    KEY `idx_attendance_date`         (`attendance_date`),
    CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
