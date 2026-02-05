-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Gen 30, 2026 alle 10:43
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `yourweek_db`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `activitylog`
--

CREATE TABLE `activitylog` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'Supporta IPv4 e IPv6',
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `activitylog`
--

INSERT INTO `activitylog` (`id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 19:50:16'),
(2, 1, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 19:50:55'),
(3, 1, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 20:27:39'),
(4, 2, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 20:36:50'),
(5, 2, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 20:45:37'),
(6, 2, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 20:47:45'),
(7, 1, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 20:48:01'),
(8, 1, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 08:04:21'),
(9, 2, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 08:04:38'),
(10, 3, 'register', 'Nuovo account creato', '::1', 'curl/8.6.0', '2026-01-24 11:35:03'),
(11, NULL, 'register', 'Nuovo account creato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 11:45:06'),
(12, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 11:45:51'),
(13, NULL, 'register', 'Nuovo account creato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 11:51:02'),
(14, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 11:51:18'),
(15, NULL, 'register', 'Nuovo account creato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 10:11:49'),
(16, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 10:12:11'),
(17, NULL, 'register', 'Nuovo account creato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 10:15:35'),
(18, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 10:15:45'),
(19, 1, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 10:16:46'),
(20, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 10:17:01'),
(21, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 10:23:21'),
(22, NULL, 'register', 'Nuovo account creato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 09:17:33'),
(23, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 09:17:53'),
(24, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 09:28:30'),
(25, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 09:29:53'),
(26, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 09:41:07'),
(27, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 09:41:24'),
(28, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 09:41:52'),
(29, NULL, 'register', 'Nuovo account creato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 09:43:02'),
(30, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 09:43:15'),
(31, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 09:43:46'),
(32, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 09:46:07'),
(33, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 09:47:20'),
(34, NULL, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 09:49:41'),
(35, 10, 'register', 'Nuovo account creato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 10:23:06'),
(36, 10, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 10:23:22'),
(37, 11, 'register', 'Nuovo account creato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 10:29:42'),
(38, 11, 'login', 'Accesso effettuato', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 10:29:55');

-- --------------------------------------------------------

--
-- Struttura della tabella `appointments`
--

CREATE TABLE `appointments` (
  `id` int(10) UNSIGNED NOT NULL,
  `nutritionist_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `duration_minutes` int(10) UNSIGNED NOT NULL DEFAULT 60,
  `appointment_type` enum('prima-visita','controllo-mensile','follow-up','urgente') DEFAULT 'controllo-mensile',
  `status` enum('scheduled','completed','cancelled','no-show') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `dietplanmeals`
--

CREATE TABLE `dietplanmeals` (
  `id` int(10) UNSIGNED NOT NULL,
  `diet_plan_id` int(10) UNSIGNED NOT NULL,
  `day_of_week` enum('lunedi','martedi','mercoledi','giovedi','venerdi','sabato','domenica') NOT NULL,
  `meal_type` enum('colazione','spuntino-mattina','pranzo','spuntino-pomeriggio','cena','spuntino-sera') NOT NULL,
  `meal_description` text NOT NULL,
  `calories` int(10) UNSIGNED DEFAULT NULL,
  `protein_grams` decimal(6,2) DEFAULT NULL,
  `carbs_grams` decimal(6,2) DEFAULT NULL,
  `fats_grams` decimal(6,2) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `dietplans`
--

CREATE TABLE `dietplans` (
  `id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `nutritionist_id` int(10) UNSIGNED NOT NULL,
  `plan_name` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `daily_calories` int(10) UNSIGNED DEFAULT NULL,
  `daily_protein_grams` decimal(6,2) DEFAULT NULL,
  `daily_carbs_grams` decimal(6,2) DEFAULT NULL,
  `daily_fats_grams` decimal(6,2) DEFAULT NULL,
  `meals_per_day` int(10) UNSIGNED DEFAULT 5,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `documents`
--

CREATE TABLE `documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL COMMENT 'Estensione file',
  `file_size` int(10) UNSIGNED NOT NULL COMMENT 'Dimensione in bytes',
  `file_path` varchar(500) NOT NULL COMMENT 'Path relativo al file',
  `mime_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `loginattempts`
--

CREATE TABLE `loginattempts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` datetime DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `loginattempts`
--

INSERT INTO `loginattempts` (`id`, `email`, `ip_address`, `attempt_time`, `success`) VALUES
(1, 'dott.rossi@yourweek.com', '::1', '2026-01-22 20:28:52', 0),
(2, 'dott.rossi@yourweek.com', '::1', '2026-01-22 20:29:07', 0),
(3, 'dott.rossi@yourweek.com', '::1', '2026-01-22 20:36:46', 0),
(4, 'dott.rossi@yourweek.com', '::1', '2026-01-22 20:36:48', 0),
(5, 'dott.rossi@yourweek.com', '::1', '2026-01-23 10:14:08', 0),
(6, 'dott.rossi@yourweek.com', '::1', '2026-01-23 10:14:10', 0),
(7, 'dott.rossi@yourweek.com', '::1', '2026-01-23 10:15:10', 0),
(8, 'dott.rossi@yourweek.com', '::1', '2026-01-23 10:15:11', 0),
(9, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:02:31', 0),
(10, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:23:54', 0),
(11, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:29:58', 0),
(12, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:30:00', 0),
(13, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:30:01', 0),
(14, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:30:02', 0),
(15, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:30:03', 0),
(16, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:30:04', 0),
(17, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:30:05', 0),
(18, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:30:06', 0),
(19, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:30:07', 0),
(20, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:30:09', 0),
(21, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:30:10', 0),
(22, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:30:28', 0),
(23, 'dott.rossi@yourweek.com', '::1', '2026-01-23 11:30:37', 0),
(24, 'dott.rossi@yourweek.com', '::1', '2026-01-23 18:26:58', 0),
(25, 'dott.rossi@yourweek.com', '::1', '2026-01-23 18:43:35', 0),
(26, 'dott.rossi@yourweek.com', '::1', '2026-01-23 18:45:11', 0),
(27, 'dott.rossi@yourweek.com', '::1', '2026-01-23 19:42:20', 0);

-- --------------------------------------------------------

--
-- Struttura della tabella `messages`
--

CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `receiver_id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `parent_message_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Per thread di messaggi',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `passwordresets`
--

CREATE TABLE `passwordresets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `patientprogress`
--

CREATE TABLE `patientprogress` (
  `id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `measurement_date` date NOT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `body_fat_percent` decimal(5,2) DEFAULT NULL,
  `muscle_mass_percent` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(5,2) DEFAULT NULL COMMENT 'BMI calcolato automaticamente',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `patientprogress`
--

INSERT INTO `patientprogress` (`id`, `patient_id`, `measurement_date`, `weight_kg`, `body_fat_percent`, `muscle_mass_percent`, `bmi`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, '2025-12-15', 85.00, 28.00, 35.00, 27.76, NULL, '2025-12-15 11:45:25', '2025-12-15 11:45:25');

--
-- Trigger `patientprogress`
--
DELIMITER $$
CREATE TRIGGER `calculate_bmi_before_insert` BEFORE INSERT ON `patientprogress` FOR EACH ROW BEGIN
    DECLARE patient_height DECIMAL(5,2);
    
    -- Ottieni altezza paziente
    SELECT height_cm INTO patient_height
    FROM Users
    WHERE id = NEW.patient_id
    LIMIT 1;
    
    -- Calcola BMI se abbiamo peso e altezza
    IF NEW.weight_kg IS NOT NULL AND patient_height IS NOT NULL AND patient_height > 0 THEN
        SET NEW.bmi = NEW.weight_kg / ((patient_height / 100) * (patient_height / 100));
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `calculate_bmi_before_update` BEFORE UPDATE ON `patientprogress` FOR EACH ROW BEGIN
    DECLARE patient_height DECIMAL(5,2);
    
    SELECT height_cm INTO patient_height
    FROM Users
    WHERE id = NEW.patient_id
    LIMIT 1;
    
    IF NEW.weight_kg IS NOT NULL AND patient_height IS NOT NULL AND patient_height > 0 THEN
        SET NEW.bmi = NEW.weight_kg / ((patient_height / 100) * (patient_height / 100));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struttura della tabella `personalnotes`
--

CREATE TABLE `personalnotes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `note_text` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('nutrizionista','paziente','admin') NOT NULL DEFAULT 'paziente',
  `nutritionist_code` varchar(10) DEFAULT NULL COMMENT 'Codice univoco per nutrizionisti',
  `birth_date` date DEFAULT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `initial_weight` decimal(5,2) DEFAULT NULL COMMENT 'Peso iniziale in kg',
  `initial_body_fat` decimal(5,2) DEFAULT NULL COMMENT 'Massa grassa iniziale in %',
  `initial_muscle_mass` decimal(5,2) DEFAULT NULL COMMENT 'Massa muscolare iniziale in %',
  `nutritionist_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID del nutrizionista assegnato',
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `registration_date` datetime NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `birth_date`, `height_cm`, `initial_weight`, `initial_body_fat`, `initial_muscle_mass`, `nutritionist_id`, `is_active`, `email_verified`, `registration_date`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'dott.rossi@yourweek.com', '$2y$12$UG4b5ysTe12X1x6rSdFcPebXJzCfuXANNNTFrjVGOEDdpzAqhJt0e', 'Marco', 'Rossi', 'nutrizionista', NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, '2025-12-15 11:45:25', '2026-01-26 10:16:46', '2025-12-15 11:45:25', '2026-01-26 10:16:46'),
(2, 'mario.bianchi@email.com', '$2y$12$UG4b5ysTe12X1x6rSdFcPebXJzCfuXANNNTFrjVGOEDdpzAqhJt0e', 'Mario', 'Bianchi', 'paziente', '1985-03-15', 175.00, 85.00, 28.00, 35.00, 1, 1, 1, '2025-12-15 11:45:25', '2026-01-24 08:04:38', '2025-12-15 11:45:25', '2026-01-24 08:04:38'),
(3, 'test@test.com', '$2y$12$BcTTiHvTcR/kcUmQNEYQBequxVxWhIzhLhNlgWxhUfqHDVtDjP6SG', 'Test', 'Utente', 'paziente', '2000-01-01', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-01-24 11:35:03', NULL, '2026-01-24 11:35:03', '2026-01-24 11:35:03'),
(10, 'mirko.verzeroli@gmail.com', '$2y$12$7gtP3o.LxPsn82vUCZ1bJuewrFs4tpfRFjZJtF5JJwzC1uby82Yjy', 'mirko', 'verzeroli', 'paziente', '2007-02-09', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-01-30 10:23:06', '2026-01-30 10:23:22', '2026-01-30 10:23:06', '2026-01-30 10:23:22'),
(11, 'lorenzogentilcore3@gmail.com', '$2y$12$vZrUJ1yggFgJZnYhHhdzy.6pDWf7QjxCPjdeCEMCxxeuUrVfDqyaW', 'lorenzo', 'gentilcore', 'nutrizionista', '2007-10-14', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-01-30 10:29:42', '2026-01-30 10:29:55', '2026-01-30 10:29:42', '2026-01-30 10:29:55');

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `v_patients_with_progress`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `v_patients_with_progress` (
`id` int(10) unsigned
,`email` varchar(255)
,`first_name` varchar(100)
,`last_name` varchar(100)
,`birth_date` date
,`height_cm` decimal(5,2)
,`initial_weight` decimal(5,2)
,`nutritionist_id` int(10) unsigned
,`registration_date` datetime
,`current_weight` decimal(5,2)
,`current_body_fat` decimal(5,2)
,`current_muscle_mass` decimal(5,2)
,`current_bmi` decimal(5,2)
,`last_measurement_date` date
);

-- --------------------------------------------------------

--
-- Struttura per vista `v_patients_with_progress`
--
DROP TABLE IF EXISTS `v_patients_with_progress`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_patients_with_progress`  AS SELECT `u`.`id` AS `id`, `u`.`email` AS `email`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, `u`.`birth_date` AS `birth_date`, `u`.`height_cm` AS `height_cm`, `u`.`initial_weight` AS `initial_weight`, `u`.`nutritionist_id` AS `nutritionist_id`, `u`.`registration_date` AS `registration_date`, `pp`.`weight_kg` AS `current_weight`, `pp`.`body_fat_percent` AS `current_body_fat`, `pp`.`muscle_mass_percent` AS `current_muscle_mass`, `pp`.`bmi` AS `current_bmi`, `pp`.`measurement_date` AS `last_measurement_date` FROM (`users` `u` left join (select `pp1`.`patient_id` AS `patient_id`,`pp1`.`weight_kg` AS `weight_kg`,`pp1`.`body_fat_percent` AS `body_fat_percent`,`pp1`.`muscle_mass_percent` AS `muscle_mass_percent`,`pp1`.`bmi` AS `bmi`,`pp1`.`measurement_date` AS `measurement_date` from `patientprogress` `pp1` where `pp1`.`measurement_date` = (select max(`pp2`.`measurement_date`) from `patientprogress` `pp2` where `pp2`.`patient_id` = `pp1`.`patient_id`)) `pp` on(`u`.`id` = `pp`.`patient_id`)) WHERE `u`.`role` = 'paziente' AND `u`.`is_active` = 1 ;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `activitylog`
--
ALTER TABLE `activitylog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indici per le tabelle `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nutritionist` (`nutritionist_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_date` (`appointment_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_datetime` (`appointment_date`,`start_time`);

--
-- Indici per le tabelle `dietplanmeals`
--
ALTER TABLE `dietplanmeals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diet_plan` (`diet_plan_id`),
  ADD KEY `idx_day` (`day_of_week`),
  ADD KEY `idx_meal_type` (`meal_type`);

--
-- Indici per le tabelle `dietplans`
--
ALTER TABLE `dietplans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_nutritionist` (`nutritionist_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indici per le tabelle `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`file_type`),
  ADD KEY `idx_uploaded` (`uploaded_at`);

--
-- Indici per le tabelle `loginattempts`
--
ALTER TABLE `loginattempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_time` (`attempt_time`),
  ADD KEY `idx_email_time` (`email`,`attempt_time`);

--
-- Indici per le tabelle `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_receiver` (`receiver_id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_conversation` (`sender_id`,`receiver_id`),
  ADD KEY `idx_parent` (`parent_message_id`);

--
-- Indici per le tabelle `passwordresets`
--
ALTER TABLE `passwordresets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indici per le tabelle `patientprogress`
--
ALTER TABLE `patientprogress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_patient_date` (`patient_id`,`measurement_date`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_date` (`measurement_date`);

--
-- Indici per le tabelle `personalnotes`
--
ALTER TABLE `personalnotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);
ALTER TABLE `personalnotes` ADD FULLTEXT KEY `idx_text` (`note_text`);

--
-- Indici per le tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `unique_nutritionist_code` (`nutritionist_code`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_nutritionist` (`nutritionist_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_registration` (`registration_date`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `activitylog`
--
ALTER TABLE `activitylog`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT per la tabella `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `dietplanmeals`
--
ALTER TABLE `dietplanmeals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `dietplans`
--
ALTER TABLE `dietplans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `loginattempts`
--
ALTER TABLE `loginattempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT per la tabella `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `passwordresets`
--
ALTER TABLE `passwordresets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `patientprogress`
--
ALTER TABLE `patientprogress`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `personalnotes`
--
ALTER TABLE `personalnotes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `activitylog`
--
ALTER TABLE `activitylog`
  ADD CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limiti per la tabella `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_nutritionist` FOREIGN KEY (`nutritionist_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointments_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `dietplanmeals`
--
ALTER TABLE `dietplanmeals`
  ADD CONSTRAINT `fk_meals_plan` FOREIGN KEY (`diet_plan_id`) REFERENCES `dietplans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `dietplans`
--
ALTER TABLE `dietplans`
  ADD CONSTRAINT `fk_dietplans_nutritionist` FOREIGN KEY (`nutritionist_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dietplans_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `fk_documents_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_parent` FOREIGN KEY (`parent_message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `passwordresets`
--
ALTER TABLE `passwordresets`
  ADD CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `patientprogress`
--
ALTER TABLE `patientprogress`
  ADD CONSTRAINT `fk_progress_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `personalnotes`
--
ALTER TABLE `personalnotes`
  ADD CONSTRAINT `fk_notes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_nutritionist` FOREIGN KEY (`nutritionist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
