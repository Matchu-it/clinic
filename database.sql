-- Clinic Appointment Reservation System
-- Database Schema
-- Generated: 2024

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(50)  NOT NULL UNIQUE,
    `email`      VARCHAR(100) NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(50)  NOT NULL,
    `last_name`  VARCHAR(50)  NOT NULL,
    `phone`      VARCHAR(20)           DEFAULT NULL,
    `role`       ENUM('admin','patient') NOT NULL DEFAULT 'patient',
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: doctors
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctors` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(50)  NOT NULL,
    `last_name`  VARCHAR(50)  NOT NULL,
    `specialty`  VARCHAR(100) NOT NULL,
    `phone`      VARCHAR(20)           DEFAULT NULL,
    `email`      VARCHAR(100)          DEFAULT NULL,
    `bio`        TEXT                  DEFAULT NULL,
    `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: schedules  (doctors 1:many schedules)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schedules` (
    `id`          INT(11) NOT NULL AUTO_INCREMENT,
    `doctor_id`   INT(11) NOT NULL,
    `day_of_week` ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    `start_time`  TIME    NOT NULL,
    `end_time`    TIME    NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: appointments  (users 1:many, doctors 1:many)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointments` (
    `id`               INT(11)  NOT NULL AUTO_INCREMENT,
    `patient_id`       INT(11)  NOT NULL,
    `doctor_id`        INT(11)  NOT NULL,
    `appointment_date` DATE     NOT NULL,
    `appointment_time` TIME     NOT NULL,
    `status`           ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
    `reason`           TEXT     NOT NULL,
    `notes`            TEXT              DEFAULT NULL,
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`)  REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: medical_records  (appointments 1:1)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `medical_records` (
    `id`             INT(11)   NOT NULL AUTO_INCREMENT,
    `appointment_id` INT(11)   NOT NULL UNIQUE,
    `diagnosis`      TEXT               DEFAULT NULL,
    `prescription`   TEXT               DEFAULT NULL,
    `notes`          TEXT               DEFAULT NULL,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Seed: default admin user  (password: Admin@1234)
-- --------------------------------------------------------
INSERT IGNORE INTO `users` (`username`,`email`,`password`,`first_name`,`last_name`,`role`)
VALUES (
    'admin',
    'admin@clinic.com',
    '$2y$12$IQJ0e.dkNyHrWFoLMoNrfOd9WOScsTXuWDs8lBJ8mu1BeahePmp06',
    'System',
    'Admin',
    'admin'
);

-- --------------------------------------------------------
-- Seed: sample doctors
-- --------------------------------------------------------
INSERT IGNORE INTO `doctors` (`first_name`,`last_name`,`specialty`,`phone`,`email`,`bio`)
VALUES
('Maria','Santos','General Medicine','09171234567','m.santos@clinic.com','Experienced general practitioner with 10+ years of service.'),
('Jose','Reyes','Cardiology','09181234567','j.reyes@clinic.com','Board-certified cardiologist specializing in heart disease prevention.'),
('Ana','Cruz','Pediatrics','09191234567','a.cruz@clinic.com','Dedicated pediatrician focused on child wellness and development.');

-- --------------------------------------------------------
-- Seed: doctor schedules
-- --------------------------------------------------------
INSERT IGNORE INTO `schedules` (`doctor_id`,`day_of_week`,`start_time`,`end_time`)
VALUES
(1,'Monday','08:00','12:00'),(1,'Wednesday','08:00','12:00'),(1,'Friday','13:00','17:00'),
(2,'Tuesday','09:00','13:00'),(2,'Thursday','09:00','13:00'),
(3,'Monday','13:00','17:00'),(3,'Wednesday','13:00','17:00'),(3,'Saturday','08:00','12:00');
