<?php
/**
 * Configuración de Base de Datos y Conexión PDO
 * Centralizamos aquí la conexión para facilitar el mantenimiento.
 */

// Configuración de la base de datos
$host     = 'localhost';
$db_name  = 'tpv_db';
$username = 'tecnicos';
$password = 'Nfa8uku4';
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en errores
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve arrays asociativos por defecto
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Uso de sentencias preparadas reales
];

try {
    // Creamos la instancia de PDO que será usada por los demás scripts
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // Si falla, devolvemos un JSON de error y detenemos la ejecución
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}
?>
