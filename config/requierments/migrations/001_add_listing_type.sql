-- Migration: add listing_type and property_type to properties and indexes
ALTER TABLE `properties`
	ADD COLUMN `listing_type` ENUM('Buy','Rent','PG/Co-living') DEFAULT 'Buy' AFTER `price`,


-- Helpful indexes for filtering
CREATE INDEX `idx_properties_price` ON `properties`(`price`);
CREATE INDEX `idx_properties_location` ON `properties`(`location`);
CREATE INDEX `idx_properties_landmark` ON `properties`(`landmark`);
CREATE INDEX `idx_properties_area` ON `properties`(`area`);
CREATE INDEX `idx_properties_configuration` ON `properties`(`configuration`);
CREATE INDEX `idx_properties_category` ON `properties`(`category_id`);