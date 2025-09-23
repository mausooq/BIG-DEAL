-- Create blog_subtitles table to store subtitle sections per blog
CREATE TABLE blog_subtitles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blog_id INT NOT NULL,
    subtitle VARCHAR(255) NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(255), -- store image path or URL
    order_no INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blog_id) REFERENCES blogs(id) ON DELETE CASCADE
);

-- Alter blog_subtitles table to make subtitle field nullable
ALTER TABLE blog_subtitles MODIFY COLUMN subtitle VARCHAR(255) NULL;