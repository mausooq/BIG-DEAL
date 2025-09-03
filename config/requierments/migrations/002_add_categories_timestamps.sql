-- Migration: Add timestamps to categories table
-- Date: 2024-01-XX

-- Add created_at column to categories table
ALTER TABLE categories ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP;

-- Add updated_at column for future use
ALTER TABLE categories ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
