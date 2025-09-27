-- Migration: Add support for multiple images per project
-- Date: 2025-01-27

-- Create project_images table for multiple images per project
CREATE TABLE IF NOT EXISTS project_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    image_filename VARCHAR(255) NOT NULL,
    display_order INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Migrate existing single images to new table
INSERT INTO project_images (project_id, image_filename, display_order, created_at)
SELECT id, image_path, 1, created_at
FROM projects 
WHERE image_path IS NOT NULL AND image_path != '';

-- Remove the old image_path column from projects table
ALTER TABLE projects DROP COLUMN IF EXISTS image_path;
