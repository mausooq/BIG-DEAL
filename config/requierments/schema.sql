CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,  -- store hashed passwords
    email VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

CREATE TABLE properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    listing_type ENUM('Buy','Rent','PG/Co-living') DEFAULT 'Buy',
    price DECIMAL(15,2),
    location VARCHAR(255),
    landmark VARCHAR(255),
    area FLOAT,  -- in sqft
    configuration VARCHAR(50),  -- e.g., 2BHK, 3BHK
    category_id INT,
    furniture_status ENUM('Furnished', 'Semi-Furnished', 'Unfurnished'),
    ownership_type ENUM('Freehold', 'Leasehold'),
    facing ENUM('East', 'West', 'North', 'South'),
    parking ENUM('Yes', 'No'),
    balcony INT DEFAULT 0,
    status ENUM('Available', 'Sold', 'Rented') DEFAULT 'Available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE property_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT,
    image_url VARCHAR(255),
    FOREIGN KEY (property_id) REFERENCES properties(id)
);

CREATE TABLE features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT,
    FOREIGN KEY (property_id) REFERENCES properties(id)
);

CREATE TABLE blogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    content TEXT,
    image_url VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    feedback TEXT,
    rating INT CHECK(rating BETWEEN 1 AND 5),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT,
    answer TEXT
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE social_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(50),
    url VARCHAR(255)
);
CREATE TABLE enquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    message TEXT,
    status ENUM('New', 'In Progress', 'Closed') DEFAULT 'New',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id)
);

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(255), -- e.g., 'Added new property', 'Deleted blog'
    details TEXT, -- Optional extra details
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id)
);
