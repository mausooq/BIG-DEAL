-- Add profile_image and home_image columns to testimonials table
ALTER TABLE testimonials 
ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL,
ADD COLUMN home_image VARCHAR(255) DEFAULT NULL;
