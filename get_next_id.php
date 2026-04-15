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
    $stmtR = $pdo->query("SELECT id FROM repairs WHERE id LIKE 'R-%'");
    $idsR = $stmtR->fetchAll(PDO::FETCH_COLUMN);
    $maxR = 0;
    foreach ($idsR as $id) {
        $num = (int)substr($id, 2);
        if ($num > $maxR) $maxR = $num;
    }
    // Lógica: Si no hay registros significativos, empezamos en 1000. 
    // Si hay, usamos el siguiente al máximo, asegurando no bajar de 1000 si es la intención original.
    $nextRepairId = ($maxR === 0 && empty($idsR)) ? 1000 : max(1000, $maxR + 1);

    // Obtener el siguiente ID para Creaciones (prefijo C-)
    $stmtC = $pdo->query("SELECT id FROM creations WHERE id LIKE 'C-%'");
    $idsC = $stmtC->fetchAll(PDO::FETCH_COLUMN);
    $maxC = 0;
    foreach ($idsC as $id) {
        $num = (int)substr($id, 2);
        if ($num > $maxC) $maxC = $num;
    }
    $nextCreateId = ($maxC === 0 && empty($idsC)) ? 5000 : max(5000, $maxC + 1);

    echo json_encode([
        'status' => 'success',
        'nextRepairId' => (int)$nextRepairId,
        'nextCreateId' => (int)$nextCreateId
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
