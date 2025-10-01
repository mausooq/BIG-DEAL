-- Migration: Add category (text) column to blogs
-- Date: 2025-10-01

-- Adds a nullable category text field after image_url
ALTER TABLE blogs
  ADD COLUMN category VARCHAR(100) NULL AFTER image_url;

-- Helpful index for filtering/listing by category
CREATE INDEX idx_blogs_category ON blogs(category);


