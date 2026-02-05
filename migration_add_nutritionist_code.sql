-- Migration: Add nutritionist_code field to users table
-- Date: 2026-01-31
-- Description: Adds a unique code field for nutritionists to allow patient assignment via code

-- Add the nutritionist_code column
ALTER TABLE `users` 
ADD COLUMN `nutritionist_code` VARCHAR(10) DEFAULT NULL AFTER `role`,
ADD UNIQUE KEY `unique_nutritionist_code` (`nutritionist_code`);

-- Generate codes for existing nutritionists
-- This will create codes in the format NUT-XXXXX where XXXXX is a 5-digit number
UPDATE `users` 
SET `nutritionist_code` = CONCAT('NUT-', LPAD(FLOOR(10000 + RAND() * 90000), 5, '0'))
WHERE `role` = 'nutrizionista' AND `nutritionist_code` IS NULL;

-- Verify no duplicates (in case of collision, which is very rare)
-- If duplicates exist, they need to be manually resolved
SELECT nutritionist_code, COUNT(*) as count 
FROM users 
WHERE nutritionist_code IS NOT NULL 
GROUP BY nutritionist_code 
HAVING count > 1;
