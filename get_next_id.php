<?php
/**
 * Calcular el Siguiente ID (Referencia) disponible
 * Para evitar conflictos, buscamos el valor numérico más alto en lugar de confiar en el autoincremento.
 */
header('Content-Type: application/json');

// Requerimos la conexión centralizada
require_once 'db.php';

try {
    // --- 1. Calcular Siguiente ID para Reparaciones (R-) ---
    $stmtR = $pdo->query("SELECT id FROM repairs WHERE id LIKE 'R-%' OR id LIKE 'r-%'");
    $idsR = $stmtR->fetchAll(PDO::FETCH_COLUMN);
    $maxR = 0;
    foreach ($idsR as $id) {
        $cleanId = preg_replace('/[^0-9]/', '', $id);
        $num = (int)$cleanId;
        if ($num > $maxR) $maxR = $num;
    }
    // Empezamos en 1000 si no hay registros
    $nextRepairId = ($maxR === 0 && empty($idsR)) ? 1000 : max(1000, $maxR + 1);

    // --- 2. Calcular Siguiente ID para Creaciones (C-) ---
    $stmtC = $pdo->query("SELECT id FROM creations WHERE id LIKE 'C-%' OR id LIKE 'c-%'");
    $idsC = $stmtC->fetchAll(PDO::FETCH_COLUMN);
    $maxC = 0;
    foreach ($idsC as $id) {
        $cleanId = preg_replace('/[^0-9]/', '', $id);
        $num = (int)$cleanId;
        if ($num > $maxC) $maxC = $num;
    }
    // Empezamos en 5000 si no hay registros
    $nextCreateId = ($maxC === 0 && empty($idsC)) ? 5000 : max(5000, $maxC + 1);

    // Devolvemos los IDs al frontend
    echo json_encode([
        'status'       => 'success',
        'nextRepairId' => (int)$nextRepairId,
        'nextCreateId' => (int)$nextCreateId
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
