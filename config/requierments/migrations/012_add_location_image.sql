-- Add optional image fields to location hierarchy tables

-- Add image field to states table
ALTER TABLE states ADD COLUMN image_url VARCHAR(255) NULL;

-- Add image field to districts table
ALTER TABLE districts ADD COLUMN image_url VARCHAR(255) NULL;

-- Add image field to cities table
ALTER TABLE cities ADD COLUMN image_url VARCHAR(255) NULL;

-- Add image field to towns table
ALTER TABLE towns ADD COLUMN image_url VARCHAR(255) NULL;
