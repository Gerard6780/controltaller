<?php
/**
 * Actualizar Registro Existente
 */
header('Content-Type: application/json');

// Requerimos la conexión centralizada
require_once 'db.php';

// Obtener datos del cuerpo de la petición (PUT/POST JSON)
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['type']) || !isset($data['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit;
}

$id = $data['id'];
$type = $data['type'];

try {
    $pdo->beginTransaction();

    if ($type === 'repair') {
        // Actualizar Reparación
        $stmt = $pdo->prepare("UPDATE repairs SET client = ?, technician = ?, problem = ?, accessories = ?, delivered = ? WHERE id = ?");
        $stmt->execute([
            $data['client'], 
            $data['technician'], 
            $data['problem'], 
            $data['accessories'] ?? '', 
            $data['delivered'] ?? 0, 
            $id
        ]);
    }
    elseif ($type === 'creation') {
        // Actualizar Creación (Ensamblaje)
        // Empaquetamos componentes en JSON para la columna 'components'
        $componentsJson = json_encode($data['components'] ?? []);
        $stmt = $pdo->prepare("UPDATE creations SET client = ?, technician = ?, delivered = ?, components = ? WHERE id = ?");
        $stmt->execute([
            $data['client'], 
            $data['technician'], 
            $data['delivered'] ?? 0, 
            $componentsJson, 
            $id
        ]);
    }
    else {
        throw new Exception('Tipo de registro desconocido');
    }

    $pdo->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    // Si algo falla, deshacemos cambios
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>