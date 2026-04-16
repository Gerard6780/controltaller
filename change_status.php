<?php
/**
 * Cambiar el Estado de Entrega (Pendiente/Entregado)
 */
header('Content-Type: application/json');

// Requerimos la conexión centralizada
require_once 'db.php';

// Obtener datos del JSON enviado
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['type']) || !isset($data['id']) || !isset($data['delivered'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit;
}

$type = $data['type'];
$id = $data['id'];
$delivered = (int)$data['delivered'];

try {
    // Determinamos la tabla según el tipo de registro
    if ($type === 'repair') {
        $stmt = $pdo->prepare("UPDATE repairs SET delivered = ? WHERE id = ?");
    } else if ($type === 'creation') {
        $stmt = $pdo->prepare("UPDATE creations SET delivered = ? WHERE id = ?");
    } else {
        throw new Exception('Tipo desconocido');
    }
    
    $stmt->execute([$delivered, $id]);
    echo json_encode(['status' => 'success']);
}
catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
