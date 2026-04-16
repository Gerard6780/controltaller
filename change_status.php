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
if (!$data || !isset($data['type']) || !isset($data['id']) || !isset($data['delivered'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit;
}

$type = $data['type'];
$id = $data['id'];
$delivered = (int)$data['delivered'];

try {
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
