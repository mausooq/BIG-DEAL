-- Add status column to admin_users table
ALTER TABLE admin_users ADD COLUMN status ENUM('active', 'suspended') DEFAULT 'active';

