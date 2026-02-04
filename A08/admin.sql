-- Admin Content Management System Database Structure

-- Admin table for authentication
CREATE TABLE `admin` (
  `adminID` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`adminID`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default admin (password: admin123)
INSERT INTO `admin` (`username`, `password`, `email`) VALUES
('admin', 'admin123', 'admin@corememories.com');

-- Add new columns to islandcontents table for admin features
ALTER TABLE `islandcontents` 
ADD COLUMN `title` varchar(200) DEFAULT NULL AFTER `content`,
ADD COLUMN `visible` tinyint(1) DEFAULT 1 AFTER `title`,
ADD COLUMN `created_at` timestamp DEFAULT CURRENT_TIMESTAMP AFTER `visible`,
ADD COLUMN `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
ADD COLUMN `view_count` int(11) DEFAULT 0 AFTER `updated_at`,
ADD COLUMN `adminID` int(11) DEFAULT 1 AFTER `view_count`,
ADD COLUMN `image_alt` varchar(200) DEFAULT NULL AFTER `image`;

-- Add new columns to islandsofpersonality table
ALTER TABLE `islandsofpersonality` 
ADD COLUMN `visible` tinyint(1) DEFAULT 1 AFTER `color`,
ADD COLUMN `created_at` timestamp DEFAULT CURRENT_TIMESTAMP AFTER `visible`,
ADD COLUMN `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
ADD COLUMN `adminID` int(11) DEFAULT 1 AFTER `updated_at`;

-- Activity log table for tracking admin actions
CREATE TABLE `admin_activity_log` (
  `logID` int(11) NOT NULL AUTO_INCREMENT,
  `adminID` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`logID`),
  KEY `adminID` (`adminID`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Update existing islandcontents with default titles
UPDATE `islandcontents` SET `title` = CONCAT(UCASE(SUBSTRING(`image`, 1, 1)), SUBSTRING(`image`, 2)) WHERE `title` IS NULL;
