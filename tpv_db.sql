-- Esquema Final de la Base de Datos para Control de Taller
-- Versión refactorizada: 16/04/2026

CREATE DATABASE IF NOT EXISTS tpv_db;
USE tpv_db;

-- Tabla de Técnicos
CREATE TABLE IF NOT EXISTS technicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- Tabla de Reparaciones
CREATE TABLE IF NOT EXISTS repairs (
    id VARCHAR(20) PRIMARY KEY,
    client VARCHAR(100) NOT NULL,
    technician VARCHAR(100) NOT NULL,
    problem TEXT,
    accessories TEXT,
    delivered TINYINT(1) DEFAULT 0,
    date DATETIME NOT NULL
);

-- Tabla de Creaciones (Ensamblajes)
-- Los componentes se guardan en formato JSON en la columna 'components'
CREATE TABLE IF NOT EXISTS creations (
    id VARCHAR(20) PRIMARY KEY,
    client VARCHAR(100) NOT NULL,
    technician VARCHAR(100) NOT NULL,
    components TEXT,
    delivered TINYINT(1) DEFAULT 0,
    date DATETIME NOT NULL
);

-- Inserción de Técnicos por defecto
TRUNCATE TABLE technicians;
INSERT INTO technicians (name) VALUES 
('Alex Linares'), 
('Carlos Muñoz'), 
('Stephane Geronimi'), 
('Dani Honrado'), 
('Gerard Anta'), 
('Xavier Lamarca');

-- Registro de prueba (Reparación)
INSERT IGNORE INTO repairs (id, client, technician, problem, accessories, delivered, date) 
VALUES ('R-1000', 'Cliente de Prueba', 'Alex Linares', 'No enciende tras actualización', 'Cargador original', 0, NOW());

-- Registro de prueba (Creación)
INSERT IGNORE INTO creations (id, client, technician, components, delivered, date) 
VALUES ('C-5000', 'Cliente de Empresa', 'Gerard Anta', '[{"label":"Placa Base","pn":"PB-123","sn":"SN-456"},{"label":"CPU","pn":"I7-12GEN","sn":"SN-CPU-789"}]', 0, NOW());