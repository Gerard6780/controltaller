<?php
/**
 * Guardar Nuevo Registro (Reparación o Creación)
 */
header('Content-Type: application/json');

// Requerimos la conexión centralizada
require_once 'db.php';

// Obtener datos del cuerpo de la petición (POST JSON)
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['type'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit;
}

$type = $data['type'];

try {
    $pdo->beginTransaction();

    if ($type === 'repair') {
        // Lógica para Reparaciones
        $stmt = $pdo->prepare("INSERT INTO repairs (id, client, technician, problem, accessories, date) VALUES (?,?,?,?,?,?)");
        $stmt->execute([
            $data['id'], 
            $data['client'], 
            $data['technician'], 
            $data['problem'], 
            $data['accessories'] ?? '', 
            $data['date']
        ]);
    }
    elseif ($type === 'creation') {
        // Lógica para Creaciones (Ensamblajes)
        // Guardamos los componentes como un JSON en la misma tabla
        $componentsJson = json_encode($data['components'] ?? []);
        $stmt = $pdo->prepare("INSERT INTO creations (id, client, technician, date, components) VALUES (?,?,?,?,?)");
        $stmt->execute([
            $data['id'], 
            $data['client'], 
            $data['technician'], 
            $data['date'], 
            $componentsJson
        ]);
    }
    else {
        throw new Exception('Tipo de registro desconocido');
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'id' => $data['id'] ?? null, 'type' => $type]);

} catch (Exception $e) {
    // Si algo falla, deshacemos los cambios
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>