-- Normalize existing values that would become invalid
UPDATE properties SET status = 'Sold' WHERE status = 'Rented';

-- Restrict enum to only 'Available' and 'Sold'
ALTER TABLE properties 
MODIFY COLUMN status ENUM('Available', 'Sold') DEFAULT 'Available';

