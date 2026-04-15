<?php
$host = 'localhost';
$db = 'tpv_db';
$user = 'tecnicos';
$pass = 'Nfa8uku4';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Añadir columna si no existe
    $stmt = $pdo->query("SHOW COLUMNS FROM creations LIKE 'components'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE creations ADD COLUMN components TEXT AFTER technician");
        echo "Columna 'components' añadida a 'creations'.\n";
    } else {
        echo "La columna 'components' ya existe.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
