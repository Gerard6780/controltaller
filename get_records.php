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

    $refFilter = isset($_GET['ref']) ? trim($_GET['ref']) : '';
    $typeFilter = isset($_GET['type']) ? trim($_GET['type']) : '';
    $refLike = "%$refFilter%";

    $repairs = [];
    $creations = [];

    // Reparaciones
    if ($typeFilter === '' || $typeFilter === 'repair') {
        if ($refFilter) {
            $stmtRepair = $pdo->prepare("SELECT id, 'repair' AS type, client, technician, problem, accessories, delivered, date FROM repairs WHERE id LIKE ? ORDER BY date DESC");
            $stmtRepair->execute([$refLike]);
        } else {
            $stmtRepair = $pdo->query("SELECT id, 'repair' AS type, client, technician, problem, accessories, delivered, date FROM repairs ORDER BY date DESC LIMIT 100");
        }
        $repairs = $stmtRepair->fetchAll();
    }

    // Creaciones
    if ($typeFilter === '' || $typeFilter === 'creation') {
        if ($refFilter) {
            $stmtCreation = $pdo->prepare("SELECT id, 'creation' AS type, client, technician, delivered, date, components FROM creations WHERE id LIKE ? ORDER BY date DESC");
            $stmtCreation->execute([$refLike]);
        } else {
            $stmtCreation = $pdo->query("SELECT id, 'creation' AS type, client, technician, delivered, date, components FROM creations ORDER BY date DESC LIMIT 100");
        }
        $creations = $stmtCreation->fetchAll();
    }

    // Recoger componentes para cada creación
    // Procesar componentes de cada creación (ahora desde campo JSON)
    foreach ($creations as &$creation) {
        $creation['components'] = json_decode($creation['components'] ?? '[]', true);
        $creation['problem'] = null;
    }

    // Mezclar registros y ordenar por fecha desc
    $records = array_merge($repairs, $creations);
    usort($records, function ($a, $b) {
        return strtotime($b['date']) <=> strtotime($a['date']);
    });

    echo json_encode($records);

} catch (PDOException $e) {
    echo json_encode([]);
}
?>