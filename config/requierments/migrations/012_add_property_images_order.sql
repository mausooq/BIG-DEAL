-- Migration: Add image_order to property_images and backfill

-- 1) Add column (idempotent where supported)
ALTER TABLE property_images
	ADD COLUMN IF NOT EXISTS image_order INT NOT NULL DEFAULT 1 AFTER image_url;

-- 2) Backfill order per property based on existing id sequence
-- This approach works across MySQL versions without window functions
UPDATE property_images p
SET p.image_order = (
	SELECT COUNT(*) FROM property_images x
	WHERE x.property_id = p.property_id AND x.id <= p.id
);

-- 3) Helpful covering index for ordered queries
CREATE INDEX IF NOT EXISTS idx_property_images_property_id_order
	ON property_images(property_id, image_order);
