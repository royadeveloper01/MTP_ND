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

-- 3. Attributes Tables (Sizes & Colors)
-- Normalized tables for product variations.
-- -----------------------------------------------------------------
CREATE TABLE sizes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE colors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_sizes (
  product_id INT NOT NULL,
  size_id INT NOT NULL,
  PRIMARY KEY (product_id, size_id),
  CONSTRAINT fk_ps_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_ps_size FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_colors (
  product_id INT NOT NULL,
  color_id INT NOT NULL,
  PRIMARY KEY (product_id, color_id),
  CONSTRAINT fk_pc_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_pc_color FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Cart Table
-- Stores items users have added to their shopping cart.
-- -----------------------------------------------------------------
CREATE TABLE cart (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  size VARCHAR(50) DEFAULT 'default',
  color VARCHAR(50) DEFAULT 'default',
  quantity INT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user_product_variation (user_id, product_id, size, color),
  CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Orders Table
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

-- 6. Order Items Table
-- Stores the individual products included in each order.
-- -----------------------------------------------------------------
CREATE TABLE order_items (
  id INT NOT NULL AUTO_INCREMENT,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  size VARCHAR(50) NOT NULL, -- The specific size chosen for the item
  color VARCHAR(50) NOT NULL, -- The specific color chosen for the item
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL, -- Stores the price at the time of purchase
  PRIMARY KEY (id),
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  token VARCHAR(255) NOT NULL UNIQUE,
  expires_at TIMESTAMP NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  used BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (id),
  INDEX idx_token (token),
  INDEX idx_user_id (user_id),
  INDEX idx_expires_at (expires_at),
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Optional: Populate the new master tables with some default values
INSERT INTO `sizes` (`name`) VALUES ('S'), ('M'), ('L'), ('XL'), ('XXL');
INSERT INTO `colors` (`name`) VALUES ('Black'), ('White'), ('Red'), ('Blue'), ('Green'), ('Gray'), ('Pink'), ('Yellow');
-- =================================================================