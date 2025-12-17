USE mtp_db;

-- 1. Add the new variation columns
ALTER TABLE cart 
ADD COLUMN size VARCHAR(50) DEFAULT 'default' AFTER product_id,
ADD COLUMN color VARCHAR(50) DEFAULT 'default' AFTER size;

-- 2. Update the Unique Constraint
-- We drop the old constraint that only checked user/product
-- and add a new one that includes size and color.
ALTER TABLE cart 
DROP INDEX uk_user_product,
ADD UNIQUE INDEX uk_user_product_variation (user_id, product_id, size, color);