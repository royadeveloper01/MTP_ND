USE mtp_db;
ALTER TABLE order_items 
ADD COLUMN size VARCHAR(50) AFTER product_id,
ADD COLUMN color VARCHAR(50) AFTER size;