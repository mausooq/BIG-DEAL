-- Normalized location hierarchy and properties_location mapping

CREATE TABLE IF NOT EXISTS states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE,
    UNIQUE(state_id, name)
);

CREATE TABLE IF NOT EXISTS cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    district_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
    UNIQUE(district_id, name)
);

CREATE TABLE IF NOT EXISTS towns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
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

