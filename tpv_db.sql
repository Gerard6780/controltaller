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
    components TEXT,
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

-- Lista Completa de Técnicos
TRUNCATE TABLE technicians;
INSERT INTO technicians (name) VALUES 
('Alex Linares'), 
('Carlos Muñoz'), 
('Stephane Geronimi'), 
('Dani Honrado'), 
('Gerard Anta'), 
('Xavier Lamarca'), 
('Daniel Palacios');

-- Registros de Prueba
INSERT IGNORE INTO repairs (id, client, technician, problem, accessories, delivered, date) 
VALUES ('R-0000', 'Cliente de Prueba', 'Dani Honrado', 'Fallo detectado post-traslado', 'Cargador', 0, NOW());

INSERT IGNORE INTO creations (id, client, technician, components, delivered, date) 
VALUES ('C-0000', 'Taller Móvil Test', 'Alex Linares', '[{"label":"Chasis","value":"Lian Li"},{"label":"Fuente","value":"Corsair 850W"}]', 0, NOW());

INSERT IGNORE INTO creation_components (creation_id, component_label, component_value) 
VALUES ('C-0000', 'Chasis', 'Lian Li'), ('C-0000', 'Fuente', 'Corsair 850W');