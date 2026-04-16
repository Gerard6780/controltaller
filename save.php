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
if (!$data || !isset($data['type'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit;
}

$type = $data['type'];

try {
    $pdo->beginTransaction();

    if ($type === 'repair') {
        $stmt = $pdo->prepare("INSERT INTO repairs (id, client, technician, problem, accessories, date) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$data['id'], $data['client'], $data['technician'], $data['problem'], $data['accessories'] ?? '', $data['date']]);
    }
    elseif ($type === 'creation') {
        // En la versión anterior guardábamos componentes en JSON en la tabla principal
        $componentsJson = json_encode($data['components'] ?? []);
        $stmt = $pdo->prepare("INSERT INTO creations (id, client, technician, date, components) VALUES (?,?,?,?,?)");
        $stmt->execute([$data['id'], $data['client'], $data['technician'], $data['date'], $componentsJson]);
    }
    else {
        throw new Exception('Tipo de registro desconocido');
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'id' => $data['id'] ?? null, 'type' => $type]);
}
catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>