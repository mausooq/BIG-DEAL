-- Migration: Add tags column to blogs
-- Date: 2025-10-01

-- Adds a nullable tags text field after category
ALTER TABLE blogs
  ADD COLUMN tags VARCHAR(255) NULL AFTER category;

-- Optional index to speed up filtering by tags (simple index)
CREATE INDEX idx_blogs_tags ON blogs(tags);


