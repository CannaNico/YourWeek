-- database_schema.sql
-- Schema database completo per YourWeek
-- Database: yourweek_db

-- Elimina database se esiste (ATTENZIONE: solo per development!)
-- DROP DATABASE IF EXISTS yourweek_db;

-- Crea database
CREATE DATABASE IF NOT EXISTS yourweek_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE yourweek_db;

-- ==========================================
-- TABELLA UTENTI (Users)
-- ==========================================
CREATE TABLE IF NOT EXISTS Users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('nutrizionista', 'paziente', 'admin') NOT NULL DEFAULT 'paziente',
    birth_date DATE NULL,
    height_cm DECIMAL(5,2) NULL,
    initial_weight DECIMAL(5,2) NULL COMMENT 'Peso iniziale in kg',
    initial_body_fat DECIMAL(5,2) NULL COMMENT 'Massa grassa iniziale in %',
    initial_muscle_mass DECIMAL(5,2) NULL COMMENT 'Massa muscolare iniziale in %',
    nutritionist_id INT UNSIGNED NULL COMMENT 'ID del nutrizionista assegnato',
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    registration_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_nutritionist (nutritionist_id),
    INDEX idx_active (is_active),
    INDEX idx_registration (registration_date),
    
    CONSTRAINT fk_users_nutritionist 
        FOREIGN KEY (nutritionist_id) 
        REFERENCES Users(id) 
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABELLA PROGRESSI PAZIENTI (PatientProgress)
-- ==========================================
CREATE TABLE IF NOT EXISTS PatientProgress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    measurement_date DATE NOT NULL,
    weight_kg DECIMAL(5,2) NULL,
    body_fat_percent DECIMAL(5,2) NULL,
    muscle_mass_percent DECIMAL(5,2) NULL,
    bmi DECIMAL(5,2) NULL COMMENT 'BMI calcolato automaticamente',
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_patient_date (patient_id, measurement_date),
    INDEX idx_patient (patient_id),
    INDEX idx_date (measurement_date),
    
    CONSTRAINT fk_progress_patient 
        FOREIGN KEY (patient_id) 
        REFERENCES Users(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABELLA APPUNTAMENTI (Appointments)
-- ==========================================
CREATE TABLE IF NOT EXISTS Appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nutritionist_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 60,
    appointment_type ENUM('prima-visita', 'controllo-mensile', 'follow-up', 'urgente') DEFAULT 'controllo-mensile',
    status ENUM('scheduled', 'completed', 'cancelled', 'no-show') DEFAULT 'scheduled',
    notes TEXT NULL,
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_nutritionist (nutritionist_id),
    INDEX idx_patient (patient_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status),
    INDEX idx_datetime (appointment_date, start_time),
    
    CONSTRAINT fk_appointments_nutritionist 
        FOREIGN KEY (nutritionist_id) 
        REFERENCES Users(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_appointments_patient 
        FOREIGN KEY (patient_id) 
        REFERENCES Users(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABELLA PIANI DIETETICI (DietPlans)
-- ==========================================
CREATE TABLE IF NOT EXISTS DietPlans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    nutritionist_id INT UNSIGNED NOT NULL,
    plan_name VARCHAR(255) NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    daily_calories INT UNSIGNED NULL,
    daily_protein_grams DECIMAL(6,2) NULL,
    daily_carbs_grams DECIMAL(6,2) NULL,
    daily_fats_grams DECIMAL(6,2) NULL,
    meals_per_day INT UNSIGNED DEFAULT 5,
    notes TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_patient (patient_id),
    INDEX idx_nutritionist (nutritionist_id),
    INDEX idx_active (is_active),
    INDEX idx_dates (start_date, end_date),
    
    CONSTRAINT fk_dietplans_patient 
        FOREIGN KEY (patient_id) 
        REFERENCES Users(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_dietplans_nutritionist 
        FOREIGN KEY (nutritionist_id) 
        REFERENCES Users(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABELLA PASTI DEL PIANO (DietPlanMeals)
-- ==========================================
CREATE TABLE IF NOT EXISTS DietPlanMeals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    diet_plan_id INT UNSIGNED NOT NULL,
    day_of_week ENUM('lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica') NOT NULL,
    meal_type ENUM('colazione', 'spuntino-mattina', 'pranzo', 'spuntino-pomeriggio', 'cena', 'spuntino-sera') NOT NULL,
    meal_description TEXT NOT NULL,
    calories INT UNSIGNED NULL,
    protein_grams DECIMAL(6,2) NULL,
    carbs_grams DECIMAL(6,2) NULL,
    fats_grams DECIMAL(6,2) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_diet_plan (diet_plan_id),
    INDEX idx_day (day_of_week),
    INDEX idx_meal_type (meal_type),
    
    CONSTRAINT fk_meals_plan 
        FOREIGN KEY (diet_plan_id) 
        REFERENCES DietPlans(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABELLA MESSAGGI (Messages)
-- ==========================================
CREATE TABLE IF NOT EXISTS Messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME NULL,
    parent_message_id INT UNSIGNED NULL COMMENT 'Per thread di messaggi',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at),
    INDEX idx_conversation (sender_id, receiver_id),
    INDEX idx_parent (parent_message_id),
    
    CONSTRAINT fk_messages_sender 
        FOREIGN KEY (sender_id) 
        REFERENCES Users(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_messages_receiver 
        FOREIGN KEY (receiver_id) 
        REFERENCES Users(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_messages_parent 
        FOREIGN KEY (parent_message_id) 
        REFERENCES Messages(id) 
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABELLA DOCUMENTI (Documents)
-- ==========================================
CREATE TABLE IF NOT EXISTS Documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL COMMENT 'Estensione file',
    file_size INT UNSIGNED NOT NULL COMMENT 'Dimensione in bytes',
    file_path VARCHAR(500) NOT NULL COMMENT 'Path relativo al file',
    mime_type VARCHAR(100) NOT NULL,
    description TEXT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_type (file_type),
    INDEX idx_uploaded (uploaded_at),
    
    CONSTRAINT fk_documents_user 
        FOREIGN KEY (user_id) 
        REFERENCES Users(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABELLA NOTE PERSONALI (PersonalNotes)
-- ==========================================
CREATE TABLE IF NOT EXISTS PersonalNotes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    note_text TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    FULLTEXT idx_text (note_text),
    
    CONSTRAINT fk_notes_user 
        FOREIGN KEY (user_id) 
        REFERENCES Users(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABELLA LOG ATTIVITÀ (ActivityLog)
-- ==========================================
CREATE TABLE IF NOT EXISTS ActivityLog (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL COMMENT 'Supporta IPv4 e IPv6',
    user_agent TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    
    CONSTRAINT fk_activity_user 
        FOREIGN KEY (user_id) 
        REFERENCES Users(id) 
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABELLA TENTATIVI LOGIN (LoginAttempts)
-- ==========================================
CREATE TABLE IF NOT EXISTS LoginAttempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    
    INDEX idx_email (email),
    INDEX idx_ip (ip_address),
    INDEX idx_time (attempt_time),
    INDEX idx_email_time (email, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABELLA TOKEN RESET PASSWORD (PasswordResets)
-- ==========================================
CREATE TABLE IF NOT EXISTS PasswordResets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_token (token),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at),
    
    CONSTRAINT fk_reset_user 
        FOREIGN KEY (user_id) 
        REFERENCES Users(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TRIGGER: Calcola BMI automaticamente
-- ==========================================
DELIMITER //

CREATE TRIGGER calculate_bmi_before_insert
BEFORE INSERT ON PatientProgress
FOR EACH ROW
BEGIN
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
END//

CREATE TRIGGER calculate_bmi_before_update
BEFORE UPDATE ON PatientProgress
FOR EACH ROW
BEGIN
    DECLARE patient_height DECIMAL(5,2);
    
    SELECT height_cm INTO patient_height
    FROM Users
    WHERE id = NEW.patient_id
    LIMIT 1;
    
    IF NEW.weight_kg IS NOT NULL AND patient_height IS NOT NULL AND patient_height > 0 THEN
        SET NEW.bmi = NEW.weight_kg / ((patient_height / 100) * (patient_height / 100));
    END IF;
END//

DELIMITER ;

-- ==========================================
-- DATI DI ESEMPIO (opzionale, per testing)
-- ==========================================

-- Nutrizionista di esempio (password: password123)
INSERT INTO Users (email, password_hash, first_name, last_name, role, is_active, email_verified) 
VALUES (
    'dott.rossi@yourweek.com',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5eDWpjI0dQSEm', -- password123
    'Marco',
    'Rossi',
    'nutrizionista',
    TRUE,
    TRUE
);

-- Paziente di esempio (password: password123)
INSERT INTO Users (
    email, password_hash, first_name, last_name, role, 
    birth_date, height_cm, initial_weight, initial_body_fat, 
    initial_muscle_mass, nutritionist_id, is_active, email_verified
) VALUES (
    'mario.bianchi@email.com',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5eDWpjI0dQSEm', -- password123
    'Mario',
    'Bianchi',
    'paziente',
    '1985-03-15',
    175.00,
    85.00,
    28.00,
    35.00,
    1, -- ID nutrizionista
    TRUE,
    TRUE
);

-- Progresso iniziale
INSERT INTO PatientProgress (patient_id, measurement_date, weight_kg, body_fat_percent, muscle_mass_percent)
VALUES (2, CURDATE(), 85.00, 28.00, 35.00);

-- ==========================================
-- VISTE UTILI (Views)
-- ==========================================

-- Vista pazienti con ultimi progressi
CREATE OR REPLACE VIEW v_patients_with_progress AS
SELECT 
    u.id,
    u.email,
    u.first_name,
    u.last_name,
    u.birth_date,
    u.height_cm,
    u.initial_weight,
    u.nutritionist_id,
    u.registration_date,
    pp.weight_kg as current_weight,
    pp.body_fat_percent as current_body_fat,
    pp.muscle_mass_percent as current_muscle_mass,
    pp.bmi as current_bmi,
    pp.measurement_date as last_measurement_date
FROM Users u
LEFT JOIN (
    SELECT 
        patient_id,
        weight_kg,
        body_fat_percent,
        muscle_mass_percent,
        bmi,
        measurement_date
    FROM PatientProgress pp1
    WHERE measurement_date = (
        SELECT MAX(measurement_date)
        FROM PatientProgress pp2
        WHERE pp2.patient_id = pp1.patient_id
    )
) pp ON u.id = pp.patient_id
WHERE u.role = 'paziente' AND u.is_active = TRUE;

-- ==========================================
-- FINE SCHEMA
-- ==========================================