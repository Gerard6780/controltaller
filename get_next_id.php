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

    // DEPURE: Ver cuántos registros hay en total
    $countR = $pdo->query("SELECT COUNT(*) FROM repairs")->fetchColumn();
    $countC = $pdo->query("SELECT COUNT(*) FROM creations")->fetchColumn();

    // Obtener el siguiente ID para Reparaciones (prefijo R-)
    // Usamos SQL para encontrar el máximo directamente
    $stmtR = $pdo->query("SELECT id FROM repairs WHERE id LIKE 'R-%' OR id LIKE 'r-%'");
    $idsR = $stmtR->fetchAll(PDO::FETCH_COLUMN);
    $maxR = 0;
    foreach ($idsR as $id) {
        $cleanId = preg_replace('/[^0-9]/', '', $id);
        $num = (int)$cleanId;
        if ($num > $maxR) $maxR = $num;
    }
    $nextRepairId = ($maxR === 0 && empty($idsR)) ? 1000 : max(1000, $maxR + 1);

    // Obtener el siguiente ID para Creaciones (prefijo C-)
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
        'nextCreateId' => (int)$nextCreateId,
        'debug' => [
            'db' => $db,
            'repairs_count' => $countR,
            'creations_count' => $countC,
            'max_repair_found' => $maxR,
            'max_creation_found' => $maxC,
            'sample_repair_ids' => array_slice($idsR, 0, 5)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
