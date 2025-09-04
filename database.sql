CREATE DATABASE volunteer_db;

USE volunteer_db;

-- Admin login
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255) -- store hashed password
);

-- Insert default admin (username=admin, password=1234)
INSERT INTO admin (username, password) VALUES ('admin', MD5('1234'));

-- Proposals
CREATE TABLE proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100),
    proposer VARCHAR(100),
    status ENUM('pending','accepted','rejected') DEFAULT 'pending'
);

-- Volunteers
CREATE TABLE volunteers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE
);
