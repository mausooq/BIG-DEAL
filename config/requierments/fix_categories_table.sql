-- Fix Categories Table Structure
-- Add missing timestamp columns to match the categories management page requirements

-- Add created_at column to categories table
ALTER TABLE `categories` 
ADD COLUMN `created_at` datetime DEFAULT current_timestamp() AFTER `name`;

-- Add updated_at column for future use
ALTER TABLE `categories` 
ADD COLUMN `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`;

-- Update existing categories to have proper timestamps
UPDATE `categories` SET `created_at` = NOW() WHERE `created_at` IS NULL;

-- Verify the table structure
DESCRIBE categories;

-- Show updated data
SELECT * FROM categories;
