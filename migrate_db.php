<?php
/**
 * Script de migración de emergencia
 */
$host = 'localhost';
$db = 'tpv_db';
$user = 'tecnicos';
$pass = 'Nfa8uku4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Asegurarse de que la columna components existe en creations
    $pdo->exec("ALTER TABLE creations ADD COLUMN IF NOT EXISTS components TEXT AFTER technician");
    
    echo "Migración completada con éxito.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
