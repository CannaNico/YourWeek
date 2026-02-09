-- Migration: Add provincia field to Users table
-- Date: 2026-02-09
-- Description: Adds provincia (province) field to enable location-based user search

USE yourweek_db;

-- Add the provincia column
ALTER TABLE Users 
ADD COLUMN provincia VARCHAR(2) NULL COMMENT 'Codice provincia italiana (es. BG, MI, RM)' AFTER last_name,
ADD INDEX idx_provincia (provincia);

-- Update existing users with sample provinces for testing
-- Nutrizionista gets BG (Bergamo)
UPDATE Users 
SET provincia = 'BG' 
WHERE role = 'nutrizionista' AND provincia IS NULL;

-- Paziente gets MI (Milano)
UPDATE Users 
SET provincia = 'MI' 
WHERE role = 'paziente' AND provincia IS NULL;

-- Verify the changes
SELECT id, email, first_name, last_name, role, provincia 
FROM Users 
ORDER BY role, id;
