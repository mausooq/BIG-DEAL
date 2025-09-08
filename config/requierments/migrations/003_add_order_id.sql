-- Migration: add order_id to faqs	
ALTER TABLE faqs
ADD COLUMN order_id INT AFTER id;
