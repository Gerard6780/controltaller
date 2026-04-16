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

$idFilter = $_GET['id'] ?? null;
$typeFilter = $_GET['type'] ?? '';
$clientFilter = $_GET['client'] ?? '';
$techFilter = $_GET['technician'] ?? '';
$problemFilter = $_GET['problem'] ?? '';
$deliveredFilter = isset($_GET['delivered']) && $_GET['delivered'] !== '' ? (int)$_GET['delivered'] : null;

try {
    $repairs = [];
    $creations = [];

    if ($typeFilter === '' || $typeFilter === 'repair') {
        $sql = "SELECT id, 'repair' AS type, client, technician, problem, accessories, delivered, date FROM repairs WHERE 1=1";
        $params = [];
        if ($idFilter) { $sql .= " AND id LIKE ?"; $params[] = "%$idFilter%"; }
        if ($clientFilter) { $sql .= " AND client LIKE ?"; $params[] = "%$clientFilter%"; }
        if ($techFilter) { $sql .= " AND technician LIKE ?"; $params[] = "%$techFilter%"; }
        if ($problemFilter) { $sql .= " AND problem LIKE ?"; $params[] = "%$problemFilter%"; }
        if ($deliveredFilter !== null) { $sql .= " AND delivered = ?"; $params[] = $deliveredFilter; }
        $sql .= " ORDER BY date DESC LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $repairs = $stmt->fetchAll();
    }

    if ($typeFilter === '' || $typeFilter === 'creation') {
        $sql = "SELECT id, 'creation' AS type, client, technician, delivered, date, components FROM creations WHERE 1=1";
        $params = [];
        if ($idFilter) { $sql .= " AND id LIKE ?"; $params[] = "%$idFilter%"; }
        if ($clientFilter) { $sql .= " AND client LIKE ?"; $params[] = "%$clientFilter%"; }
        if ($techFilter) { $sql .= " AND technician LIKE ?"; $params[] = "%$techFilter%"; }
        if ($deliveredFilter !== null) { $sql .= " AND delivered = ?"; $params[] = $deliveredFilter; }
        $sql .= " ORDER BY date DESC LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $creations = $stmt->fetchAll();

        foreach ($creations as &$creation) {
            $creation['components'] = json_decode($creation['components'] ?? '[]', true);
            $creation['problem'] = null;
        }
    }

    $all = array_merge($repairs, $creations);
    usort($all, function($a, $b) { return strcmp($b['date'], $a['date']); });
    echo json_encode(array_values($all));
}
catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>