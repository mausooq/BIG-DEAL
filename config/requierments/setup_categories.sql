-- Setup Categories Table
-- Run this script to properly set up the categories table

-- First, check if categories table exists and add missing columns
ALTER TABLE categories 
ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Insert some sample categories if the table is empty
INSERT INTO categories (name) 
SELECT * FROM (
    SELECT 'Residential' as name
    UNION ALL SELECT 'Commercial'
    UNION ALL SELECT 'Villa'
    UNION ALL SELECT 'Apartment'
    UNION ALL SELECT 'Office Space'
    UNION ALL SELECT 'Retail Space'
    UNION ALL SELECT 'Industrial'
    UNION ALL SELECT 'Land'
) AS temp
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = temp.name);

-- Verify the table structure
DESCRIBE categories;

-- Show sample data
SELECT * FROM categories;
