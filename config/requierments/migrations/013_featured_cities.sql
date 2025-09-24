-- Migration: Featured cities with ordering (limit of 5 enforced by app logic)

-- 1) Create table if not exists
CREATE TABLE IF NOT EXISTS featured_cities (
	id INT AUTO_INCREMENT PRIMARY KEY,
	city_id INT NOT NULL UNIQUE,
	order_id INT NOT NULL DEFAULT 1,
	created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT fk_featured_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
);

-- 2) Ensure order_id exists (for environments where table existed without it)
ALTER TABLE featured_cities
	ADD COLUMN IF NOT EXISTS order_id INT NOT NULL DEFAULT 1 AFTER city_id;

-- 3) Helpful indexes
CREATE INDEX IF NOT EXISTS idx_featured_cities_order ON featured_cities(order_id);
CREATE INDEX IF NOT EXISTS idx_featured_cities_created ON featured_cities(created_at);






