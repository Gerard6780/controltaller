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

    // Obtener el siguiente ID para Reparaciones (R-)
    $stmtR = $pdo->query("SELECT id FROM repairs WHERE id LIKE 'R-%' OR id LIKE 'r-%'");
    $idsR = $stmtR->fetchAll(PDO::FETCH_COLUMN);
    $maxR = 0;
    foreach ($idsR as $id) {
        $cleanId = preg_replace('/[^0-9]/', '', $id);
        $num = (int)$cleanId;
        if ($num > $maxR) $maxR = $num;
    }
    $nextRepairId = ($maxR === 0 && empty($idsR)) ? 1000 : max(1000, $maxR + 1);

    // Obtener el siguiente ID para Creaciones (C-)
    $stmtC = $pdo->query("SELECT id FROM creations WHERE id LIKE 'C-%' OR id LIKE 'c-%'");
    $idsC = $stmtC->fetchAll(PDO::FETCH_COLUMN);
    $maxC = 0;
    foreach ($idsC as $id) {
        $cleanId = preg_replace('/[^0-9]/', '', $id);
        $num = (int)$cleanId;
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
