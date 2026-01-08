-- Database schema for Hotel Reservation System
CREATE DATABASE IF NOT EXISTS hotel_reservation;
USE hotel_reservation;

-- Drop existing tables for clean import
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS hotels;
DROP TABLE IF EXISTS reservation_status;
DROP TABLE IF EXISTS logs;
DROP TABLE IF EXISTS exports;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE hotels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE reservation_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    hotel_id INT NOT NULL,
    user_id INT NOT NULL,
    reservation_time DATETIME NOT NULL,
    status_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (status_id) REFERENCES reservation_status(id)
) ENGINE=InnoDB;

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    amount DECIMAL(10,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'unpaid',
    method VARCHAR(50) DEFAULT 'cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id)
) ENGINE=InnoDB;

CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    ip_address VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE exports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    filter VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Seed data
INSERT INTO roles (name) VALUES ('admin'), ('staff');
INSERT INTO reservation_status (name) VALUES ('pending'), ('approved'), ('rejected');

INSERT INTO hotels (name, description) VALUES
('City Center Hotel', 'Downtown rooms and suites'),
('Beach Resort', 'Resort with sea view'),
('Conference Hall', 'Meetings and events space');

-- Optional: create a default admin (password: admin123) and staff (password: staff123)
-- Hashes generated via password_hash in PHP
INSERT INTO users (role, name, email, password) VALUES
('admin', 'Default Admin', 'admin@example.com', '$2y$10$wH8tJYdGKuGInxjeRGnaYOUOkIBgzHuLlHotE5RX7V6czS4fFqqmi'), -- admin123
('staff', 'Default Staff', 'staff@example.com', '$2y$10$6w7UOeQqc/QAdzBsziEt9uEM9Yf2r/.uwZQzQ0imeISFRCGDpa2ga'); -- staff123

INSERT INTO customers (name, contact) VALUES
('Jane Customer', 'jane@example.com'),
('John Client', 'john@example.com');
