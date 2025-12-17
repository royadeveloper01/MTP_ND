USE mtp_db;
ALTER TABLE cart 
ADD COLUMN size VARCHAR(50) DEFAULT 'default' AFTER product_id,
ADD COLUMN color VARCHAR(50) DEFAULT 'default' AFTER size;