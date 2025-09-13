-- Create properties_location table to store detailed location information for properties
CREATE TABLE properties_location (
    id INT AUTO_INCREMENT PRIMARY KEY,
    properties_id INT NOT NULL,
    state VARCHAR(255) NOT NULL,
    district VARCHAR(255) NOT NULL,
    city VARCHAR(255) NOT NULL,
    town VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (properties_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Alter blog_subtitles table to make subtitle field nullable
ALTER TABLE blog_subtitles MODIFY COLUMN subtitle VARCHAR(255) NULL;