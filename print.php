<?php
/**
 * MODUL4 - Linux Print Proxy for CUPS (v2.2 Debug)
 */

header('Content-Type: application/json');

// --- 1. SOPORTE PARA TERMINAL (CLI) Y WEB ---
if (php_sapi_name() === 'cli') {
    // En CLI, los parámetros vienen en $argv
    $id = isset($argv[1]) ? $argv[1] : null;
    $printer = isset($argv[2]) ? strtolower($argv[2]) : 'ql570';
    $copies = isset($argv[3]) ? (int)$argv[3] : 1;
} else {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    $printer = isset($_GET['printer']) ? strtolower($_GET['printer']) : 'ql570';
    $copies = isset($_GET['copies']) ? (int)$_GET['copies'] : 1;
}

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'No ID provided. Uso CLI: php print.php ID [printer] [copies]']);
    exit;
}

// --- 2. CONFIGURACIÓN BD ---
$host = 'localhost';
$db = 'tpv_db';
$user = 'tecnicos';
$pass = 'Nfa8uku4';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $record = null;
    $type = (strpos($id, 'R-') === 0) ? 'repair' : 'creation';

    if ($type === 'repair') {
        $stmt = $pdo->prepare("SELECT * FROM repairs WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM creations WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        if ($record) {
            $stmtC = $pdo->prepare("SELECT component_label, component_value FROM creation_components WHERE creation_id = ?");
            $stmtC->execute([$id]);
            $record['components'] = $stmtC->fetchAll();
        }
    }

    if (!$record) {
        $stmt_ids = $pdo->query("SELECT id FROM repairs LIMIT 5");
        $available = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);
        throw new Exception("ID '$id' no encontrado en la base de datos MySQL. IDs de ejemplo disponibles: " . implode(", ", $available));
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// --- 3. CONFIGURACIÓN DE PÁGINA ---
$isZebra = ($printer === 'gk420d');
$w = $isZebra ? '100mm' : '62mm';
$h = $isZebra ? '150mm' : '29mm';

// Generar HTML
ob_start();
?>
<html>
<head>
<meta charset='UTF-8'>
<script src='https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js'></script>
<style>
    body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; color: #000; }
    <?php if($isZebra): ?>
    .page-zebra { width: 100mm; height: 150mm; padding: 5mm; box-sizing: border-box; }
    .logo { font-size: 24px; font-weight: 800; text-align: center; border-bottom: 2px solid #000; }
    .data { margin-top: 15px; font-size: 14px; }
    <?php else: ?>
    .page-brother { width: 62mm; height: 29mm; padding: 3px; box-sizing: border-box; text-align: center; }
    .ref-id { font-weight: 800; font-size: 22px; margin-top: 5px; }
    <?php endif; ?>
    svg#barc { width: 100%; height: 50px; }
</style>
</head>
<body>
    <?php if($isZebra): ?>
    <div class="page-zebra">
        <div class="logo">MODUL 4</div>
        <div class="data">
            <strong>REF: <?php echo htmlspecialchars($id); ?></strong><br>
            Cliente: <?php echo htmlspecialchars($record['client']); ?><br>
            Técnico: <?php echo htmlspecialchars($record['technician']); ?><br>
            Fecha: <?php echo htmlspecialchars($record['date']); ?><br><br>
            <?php if($type === 'repair'): ?>
                Parte: <?php echo nl2br(htmlspecialchars($record['problem'])); ?>
            <?php else: ?>
                Equipo nuevo: <?php echo count($record['components']); ?> componentes.
            <?php endif; ?>
        </div>
        <div style="text-align:center; margin-top:20px;"><svg id="barc"></svg></div>
    </div>
    <?php else: ?>
    <div class="page-brother">
        <div class="ref-id">REF: <?php echo htmlspecialchars($id); ?></div>
        <svg id="barc"></svg>
    </div>
    <?php endif; ?>
    <script>
        JsBarcode("#barc", "<?php echo addslashes($id); ?>", {
            format: "CODE128",
            displayValue: false,
            height: 50,
            margin: 0
        });
    </script>
</body>
</html>
<?php
$html = ob_get_clean();

// --- 4. PROCESAMIENTO ---
$uid = uniqid();
$htmlFile = "/tmp/print_$uid.html";
$pdfFile = "/tmp/print_$uid.pdf";
file_put_contents($htmlFile, $html);

// 2>&1 importante para capturar errores de wkhtmltopdf
$cmdPdf = "wkhtmltopdf --enable-javascript --javascript-delay 300 "
        . "--page-width $w --page-height $h "
        . escapeshellarg($htmlFile) . " "
        . escapeshellarg($pdfFile) . " 2>&1";

exec($cmdPdf, $outPdf, $retPdf);

if ($retPdf !== 0) {
    echo json_encode(['status' => 'error', 'message' => 'Error al generar el PDF', 'debug' => $outPdf]);
    @unlink($htmlFile);
    exit;
}

// --- 5. IMPRESIÓN ---
$dest = ($printer === 'gk420d') ? 'GK420d' : 'QL-570';
// 2>&1 importante para capturar errores de CUPS/lp
$cmdPrint = "lp -d " . escapeshellarg($dest) . " " . escapeshellarg($pdfFile) . " 2>&1";

$allSuccess = true;
$debugOutput = [];

for ($i = 0; $i < $copies; $i++) {
    $outPrint = [];
    $retPrint = 0;
    exec($cmdPrint, $outPrint, $retPrint);
    if ($retPrint !== 0) {
        $allSuccess = false;
        $debugOutput = array_merge($debugOutput, $outPrint);
    }
}

@unlink($htmlFile);
@unlink($pdfFile);

if ($allSuccess) {
    echo json_encode(['status' => 'success', 'msg' => 'Trabajo enviado a ' . $dest, 'printer' => $dest, 'copies' => $copies]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error en el comando de impresión (lp)', 'debug' => $debugOutput, 'printer' => $dest]);
}