<?php
/**
 * Recuperar Registros para el Historial (Filtrado y Búsqueda)
 */
header('Content-Type: application/json');

// Requerimos la conexión centralizada
require_once 'db.php';

// Obtener parámetros de filtrado si existen
$idFilter        = $_GET['id'] ?? null;
$typeFilter      = $_GET['type'] ?? '';
$clientFilter    = $_GET['client'] ?? '';
$techFilter      = $_GET['technician'] ?? '';
$problemFilter   = $_GET['problem'] ?? '';
$deliveredFilter = isset($_GET['delivered']) && $_GET['delivered'] !== '' ? (int)$_GET['delivered'] : null;

try {
    $repairs = [];
    $creations = [];

    // --- REPARACIONES ---
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

    // --- CREACIONES ---
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

        // Procesar el campo JSON de componentes para que el Frontend lo reciba como array
        foreach ($creations as &$creation) {
            $creation['components'] = json_decode($creation['components'] ?? '[]', true);
            $creation['problem'] = null; // Las creaciones no tienen campo 'problem' plano
        }
    }

    // Combinar y ordenar por fecha
    $all = array_merge($repairs, $creations);
    usort($all, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    echo json_encode(array_values($all));

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>