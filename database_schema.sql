-- =================================================================
-- MTP STORE DATABASE SCHEMA
-- Version: 3.0
-- Note: This schema is consistent with the PHP application code and includes cart/order functionality.
-- =================================================================

DROP DATABASE IF EXISTS mtp_db; -- For development, ok to drop. Use with caution in production.
CREATE DATABASE mtp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mtp_db;

-- 1. Users Table
-- Stores user account information.
-- -----------------------------------------------------------------
CREATE TABLE users (
  id INT NOT NULL AUTO_INCREMENT,
  fname VARCHAR(100) NOT NULL,
  lname VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL, -- For bcrypt hashed passwords
  phone_number VARCHAR(20) NULL,
  is_admin BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert the default admin user. The password is 'admin'.
INSERT INTO `users` (`fname`, `lname`, `email`, `password_hash`, `is_admin`) VALUES
('Admin', 'User', 'admin@gmail.com', '$2a$12$CvIT7FOCNYCGB08rk8I6NePjDIHUHwz7EOZf/Zb/UZrB9u63366na', TRUE); -- Valid hash for 'admin'

-- 2. Products Table
-- Stores product information, matching the structure in add.php and edit.php.
-- -----------------------------------------------------------------
CREATE TABLE products (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  category VARCHAR(50) NULL, -- e.g., 'male', 'female'
  description TEXT NULL,
  image VARCHAR(2048) NULL, -- Stores the image URL
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_product_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Cart Table
-- Stores items users have added to their shopping cart.
-- -----------------------------------------------------------------
CREATE TABLE cart (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user_product (user_id, product_id), -- Ensures one cart entry per product for a user
  CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Orders Table
-- Stores high-level information for each order.
-- -----------------------------------------------------------------
CREATE TABLE orders (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  shipping_address TEXT NOT NULL,
  phone_number VARCHAR(20) NULL,
  payment_method VARCHAR(50) DEFAULT 'COD',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Order Items Table
-- Stores the individual products included in each order.
-- -----------------------------------------------------------------
CREATE TABLE order_items (
  id INT NOT NULL AUTO_INCREMENT,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL, -- Stores the price at the time of purchase
  PRIMARY KEY (id),
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- =================================================================