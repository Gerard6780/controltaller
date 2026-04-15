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
}
catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

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
        $stmt = $pdo->prepare("UPDATE repairs SET client = ?, technician = ?, problem = ?, accessories = ?, delivered = ? WHERE id = ?");
        $stmt->execute([$data['client'], $data['technician'], $data['problem'], $data['accessories'] ?? '', $data['delivered'] ?? 0, $id]);
    }
    elseif ($type === 'creation') {
        $componentsJson = json_encode($data['components'] ?? []);
        $stmt = $pdo->prepare("UPDATE creations SET client = ?, technician = ?, delivered = ?, components = ? WHERE id = ?");
        $stmt->execute([$data['client'], $data['technician'], $data['delivered'] ?? 0, $componentsJson, $id]);
    }
    else {
        throw new Exception('Tipo de registro desconocido');
    }

    $pdo->commit();
    echo json_encode(['status' => 'success']);
}
catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>