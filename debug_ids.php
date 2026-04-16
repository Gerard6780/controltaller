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

    $repairs = $pdo->query("SELECT id FROM repairs ORDER BY id DESC LIMIT 5")->fetchAll();
    $creations = $pdo->query("SELECT id FROM creations ORDER BY id DESC LIMIT 5")->fetchAll();

    echo json_encode([
        'repairs' => $repairs,
        'creations' => $creations
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
