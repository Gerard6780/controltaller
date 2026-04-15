<?php
header('Content-Type: application/json');

$host = 'localhost';
$db = 'tpv_db';
$user = 'tecnicos';
$pass = 'Nfa8uku4';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Obtener el siguiente ID para Reparaciones (prefijo R-)
    // Buscamos el máximo valor numérico después del prefijo 'R-'
    $stmtR = $pdo->query("SELECT MAX(CAST(SUBSTRING(id, 3) AS UNSIGNED)) as max_id FROM repairs WHERE id LIKE 'R-%'");
    $rowR = $stmtR->fetch();
    $maxR = $rowR['max_id'];
    
    // Si no hay registros o el máximo es menor a 1000, empezamos en 1000
    // (Nota: Ajustado a 1000 según la lógica original de app.js)
    $nextRepairId = ($maxR !== null) ? (max(1000, $maxR + 1)) : 1000;

    // Obtener el siguiente ID para Creaciones (prefijo C-)
    $stmtC = $pdo->query("SELECT MAX(CAST(SUBSTRING(id, 3) AS UNSIGNED)) as max_id FROM creations WHERE id LIKE 'C-%'");
    $rowC = $stmtC->fetch();
    $maxC = $rowC['max_id'];
    
    // Si no hay registros o el máximo es menor a 5000, empezamos en 5000
    $nextCreateId = ($maxC !== null) ? (max(5000, $maxC + 1)) : 5000;

    echo json_encode([
        'status' => 'success',
        'nextRepairId' => (int)$nextRepairId,
        'nextCreateId' => (int)$nextCreateId
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
