<?php
/**
 * Eliminar Registro Permanentemente
 */
header('Content-Type: application/json');

// Requerimos la conexión centralizada
require_once 'db.php';

// Obtener datos del cuerpo de la petición
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['type']) || !isset($data['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit;
}

$type = $data['type'];
$id = $data['id'];

try {
    $pdo->beginTransaction();

    if ($type === 'repair') {
        // Eliminar de la tabla de reparaciones
        $stmt = $pdo->prepare("DELETE FROM repairs WHERE id = ?");
        $stmt->execute([$id]);
    }
    elseif ($type === 'creation') {
        /**
         * NOTA: Ya no necesitamos borrar de 'creation_components' porque
         * ahora guardamos los componentes en un campo JSON dentro de 'creations'.
         */
        $stmt = $pdo->prepare("DELETE FROM creations WHERE id = ?");
        $stmt->execute([$id]);
    }
    else {
        throw new Exception('Tipo de registro desconocido');
    }

    $pdo->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>