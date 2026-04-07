CREATE DATABASE IF NOT EXISTS tpv_db;
USE tpv_db;

CREATE TABLE IF NOT EXISTS technicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS repairs (
    id VARCHAR(20) PRIMARY KEY,
    client VARCHAR(100) NOT NULL,
    technician VARCHAR(100) NOT NULL,
    problem TEXT,
    accessories TEXT,
    delivered TINYINT(1) DEFAULT 0,
    date DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS creations (
    id VARCHAR(20) PRIMARY KEY,
    client VARCHAR(100) NOT NULL,
    technician VARCHAR(100) NOT NULL,
    delivered TINYINT(1) DEFAULT 0,
    date DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS creation_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creation_id VARCHAR(20) NOT NULL,
    component_label VARCHAR(50),
    component_value VARCHAR(100),
    FOREIGN KEY (creation_id) REFERENCES creations(id) ON DELETE CASCADE
);

-- Datos iniciales y de prueba
INSERT IGNORE INTO technicians (name) VALUES ('Alex Linares'), ('Dani Honrado');

INSERT IGNORE INTO repairs (id, client, technician, problem, accessories, delivered, date) 
VALUES ('R-0000', 'Cliente de Prueba', 'Dani Honrado', 'Fallo de alimentación', 'Cargador', 0, NOW());

INSERT IGNORE INTO creations (id, client, technician, delivered, date) 
VALUES ('C-0000', 'Taller Test', 'Alex Linares', 0, NOW());

INSERT IGNORE INTO creation_components (creation_id, component_label, component_value) 
VALUES ('C-0000', 'Placa Base', 'ASUS Z790'), ('C-0000', 'Gráfica', 'RTX 4070');