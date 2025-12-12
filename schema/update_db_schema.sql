USE mtp_db;

-- Create Sizes Table
CREATE TABLE IF NOT EXISTS sizes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Colors Table
CREATE TABLE IF NOT EXISTS colors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Product_Sizes Junction Table
CREATE TABLE IF NOT EXISTS product_sizes (
  product_id INT NOT NULL,
  size_id INT NOT NULL,
  PRIMARY KEY (product_id, size_id),
  CONSTRAINT fk_ps_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_ps_size FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Product_Colors Junction Table
CREATE TABLE IF NOT EXISTS product_colors (
  product_id INT NOT NULL,
  color_id INT NOT NULL,
  PRIMARY KEY (product_id, color_id),
  CONSTRAINT fk_pc_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_pc_color FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default attributes
INSERT IGNORE INTO sizes (name) VALUES ('S'), ('M'), ('L'), ('XL'), ('XXL');
INSERT IGNORE INTO colors (name) VALUES ('Black'), ('White'), ('Red'), ('Blue'), ('Green');
