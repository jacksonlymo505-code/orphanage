-- Migration: create documents table
CREATE TABLE IF NOT EXISTS documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  user_email VARCHAR(255),
  filename VARCHAR(255),
  original_name VARCHAR(255),
  status ENUM('received','submitted','reviewed','accepted','rejected') DEFAULT 'received',
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  notes TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
