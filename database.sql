
CREATE DATABASE IF NOT EXISTS electricity_bill_db;
USE electricity_bill_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee', 'user') NOT NULL DEFAULT 'user',
    name VARCHAR(32) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1
);

CREATE TABLE consumers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    service_number VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(32) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    pincode VARCHAR(6) NOT NULL,
    phone VARCHAR(10) NOT NULL,
    email VARCHAR(100),
    category ENUM('household', 'commercial', 'industry') NOT NULL,
    meter_number VARCHAR(20) NOT NULL,
    connection_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consumer_id INT NOT NULL,
    reading_value DECIMAL(10,2) NOT NULL,
    reading_date DATE NOT NULL,
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consumer_id) REFERENCES consumers(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

CREATE TABLE bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(30) UNIQUE NOT NULL,
    consumer_id INT NOT NULL,
    service_number VARCHAR(20) NOT NULL,
    billing_start_date DATE NOT NULL,
    billing_end_date DATE NOT NULL,
    previous_reading DECIMAL(10,2) NOT NULL DEFAULT 0,
    current_reading DECIMAL(10,2) NOT NULL,
    units_consumed DECIMAL(10,2) NOT NULL,
    category ENUM('household', 'commercial', 'industry') NOT NULL,
    basic_charge DECIMAL(10,2) NOT NULL,
    energy_charge DECIMAL(10,2) NOT NULL,
    fuel_adjustment DECIMAL(10,2) DEFAULT 0,
    electricity_duty DECIMAL(10,2) DEFAULT 0,
    meter_rent DECIMAL(10,2) DEFAULT 20.00,
    total_amount DECIMAL(10,2) NOT NULL,
    fine_amount DECIMAL(10,2) DEFAULT 0,
    grand_total DECIMAL(10,2) NOT NULL,
    due_date_without_fine DATE NOT NULL,
    due_date_with_fine DATE NOT NULL,
    is_paid TINYINT(1) DEFAULT 0,
    paid_date DATE,
    paid_marked_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consumer_id) REFERENCES consumers(id) ON DELETE CASCADE,
    FOREIGN KEY (paid_marked_by) REFERENCES users(id)
);

CREATE TABLE rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('household', 'commercial', 'industry') NOT NULL,
    slab_start INT NOT NULL,
    slab_end INT NOT NULL,
    rate_per_unit DECIMAL(6,2) NOT NULL,
    basic_charge DECIMAL(10,2) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    effective_from DATE NOT NULL,
    UNIQUE KEY unique_category_slab (category, slab_start, slab_end, effective_from)
);

CREATE TABLE service_number_counter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('household', 'commercial', 'industry') NOT NULL UNIQUE,
    last_number INT NOT NULL DEFAULT 0
);

CREATE TABLE bill_number_counter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_prefix VARCHAR(8) NOT NULL UNIQUE,
    last_number INT NOT NULL DEFAULT 0
);

INSERT INTO users (username, password, role, name, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', 'admin@electricity.com');

INSERT INTO service_number_counter (category, last_number) VALUES
('household', 0),
('commercial', 0),
('industry', 0);

INSERT INTO rates (category, slab_start, slab_end, rate_per_unit, basic_charge, effective_from) VALUES
('household', 0, 50, 3.50, 50.00, '2024-01-01'),
('household', 51, 100, 4.50, 50.00, '2024-01-01'),
('household', 101, 200, 5.50, 75.00, '2024-01-01'),
('household', 201, 500, 6.50, 100.00, '2024-01-01'),
('household', 501, 999999, 7.50, 150.00, '2024-01-01');

INSERT INTO rates (category, slab_start, slab_end, rate_per_unit, basic_charge, effective_from) VALUES
('commercial', 0, 100, 5.00, 100.00, '2024-01-01'),
('commercial', 101, 500, 5.50, 150.00, '2024-01-01'),
('commercial', 501, 999999, 6.00, 200.00, '2024-01-01');

INSERT INTO rates (category, slab_start, slab_end, rate_per_unit, basic_charge, effective_from) VALUES
('industry', 0, 500, 4.00, 200.00, '2024-01-01'),
('industry', 501, 2000, 4.25, 300.00, '2024-01-01'),
('industry', 2001, 999999, 4.50, 500.00, '2024-01-01');
