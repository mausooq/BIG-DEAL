DATABASE big deal;

CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,  -- store hashed passwords
    email VARCHAR(100),
    status ENUM('active', 'suspended') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    image VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    map_embed_link TEXT,
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

-- Helpful indexes for filtering (from migrations)
CREATE INDEX idx_properties_price ON properties(price);
CREATE INDEX idx_properties_location ON properties(location);
CREATE INDEX idx_properties_landmark ON properties(landmark);
CREATE INDEX idx_properties_area ON properties(area);
CREATE INDEX idx_properties_configuration ON properties(configuration);
CREATE INDEX idx_properties_category ON properties(category_id);

CREATE TABLE property_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT,
    image_url VARCHAR(255),
    image_order INT DEFAULT 1,
    FOREIGN KEY (property_id) REFERENCES properties(id)
);

-- Helpful index for ordered image retrieval
CREATE INDEX idx_property_images_order ON property_images(property_id, image_order);

CREATE TABLE features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT,
    FOREIGN KEY (property_id) REFERENCES properties(id)
);

-- Locations
CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    place_name VARCHAR(100) NOT NULL,
    image VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE blogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    content TEXT,
    image_url VARCHAR(255),
    category VARCHAR(100) NULL,
    tags VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Blog subtitles (sections under a blog)
CREATE TABLE blog_subtitles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blog_id INT NOT NULL,
    subtitle VARCHAR(255) NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(255),
    order_no INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blog_id) REFERENCES blogs(id) ON DELETE CASCADE
);

CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    feedback TEXT,
    rating INT CHECK(rating BETWEEN 1 AND 5),
    profile_image VARCHAR(255) DEFAULT NULL,
    home_image VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
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

-- Normalized location hierarchy and mapping for properties
CREATE TABLE IF NOT EXISTS states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    image_url VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    image_url VARCHAR(255) NULL,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE,
    UNIQUE(state_id, name)
);

CREATE TABLE IF NOT EXISTS cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    district_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    image_url VARCHAR(255) NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
    UNIQUE(district_id, name)
);

CREATE TABLE IF NOT EXISTS towns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    image_url VARCHAR(255) NULL,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE,
    UNIQUE(city_id, name)
);

CREATE TABLE IF NOT EXISTS properties_location (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    state_id INT NOT NULL,
    district_id INT NOT NULL,
    city_id INT NOT NULL,
    town_id INT NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE,  
    FOREIGN KEY (town_id) REFERENCES towns(id) ON DELETE CASCADE
);

-- Featured cities
CREATE TABLE IF NOT EXISTS featured_cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city_id INT NOT NULL,
    priority INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
);
 -- Projects ,our Builds
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    order_id INT DEFAULT 1,
    location VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Project Images for multiple images per project
CREATE TABLE project_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    image_filename VARCHAR(255) NOT NULL,
    display_order INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
