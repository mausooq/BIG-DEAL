-- Add map embed link column to properties table
ALTER TABLE properties 
ADD COLUMN map_embed_link TEXT AFTER description;

