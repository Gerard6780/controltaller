CREATE DATABASE IF NOT EXISTS tpv_db;
USE tpv_db;

CREATE TABLE technicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE repairs (
    id VARCHAR(20) PRIMARY KEY,
    client VARCHAR(100) NOT NULL,
    technician VARCHAR(100) NOT NULL,
    problem TEXT,
    accessories TEXT,
    date DATETIME NOT NULL
);

CREATE TABLE creations (
    id VARCHAR(20) PRIMARY KEY,
    client VARCHAR(100) NOT NULL,
    technician VARCHAR(100) NOT NULL,
    date DATETIME NOT NULL
);

CREATE TABLE creation_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creation_id VARCHAR(20) NOT NULL,
    component_label VARCHAR(50),
    component_value VARCHAR(100),
    FOREIGN KEY (creation_id) REFERENCES creations(id) ON DELETE CASCADE
);