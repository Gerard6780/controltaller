<?php
/**
 * MODUL4 - Linux Print Proxy for CUPS (v2.3 Premium)
 * Diseño Horizontal optimizado para Zebra GK420d 150x100mm
 */

header('Content-Type: application/json');

// --- 1. SOPORTE CLI / WEB ---
if (php_sapi_name() === 'cli') {
    $id = isset($argv[1]) ? $argv[1] : null;
    $printer = isset($argv[2]) ? strtolower($argv[2]) : 'ql570';
    $copies = isset($argv[3]) ? (int)$argv[3] : 1;
} else {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    $printer = isset($_GET['printer']) ? strtolower($_GET['printer']) : 'ql570';
    $copies = isset($_GET['copies']) ? (int)$_GET['copies'] : 1;
}

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'No ID provided.']);
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
        throw new Exception("ID no encontrado en DB.");
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// --- 3. PROCESAR LOGO (Base64) ---
$logoPath = __DIR__ . '/images/logo_modul4-6.png';
$logoBase64 = "";
if (file_exists($logoPath)) {
    $logoBase64 = base64_encode(file_get_contents($logoPath));
}

// --- 4. CONFIGURACIÓN PÁGINA ---
$isZebra = ($printer === 'gk420d');
$w = $isZebra ? '150mm' : '62mm'; // Invertimos para horizontal
$h = $isZebra ? '100mm' : '29mm';

ob_start();
?>
<html>
<head>
<meta charset='UTF-8'>
<script src='https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js'></script>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; color: #1a1a1a; }
    
    /* Layout Premium Horizontal GK420d (150x100) */
    <?php if($isZebra): ?>
    .ticket { width: 150mm; height: 100mm; padding: 6mm; box-sizing: border-box; display: flex; flex-direction: column; }
    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #000; padding-bottom: 5px; margin-bottom: 15px; }
    .header .title { text-align: right; }
    .header .title h1 { margin: 0; font-size: 18px; color: #c00; }
    .header .title p { margin: 0; font-size: 11px; font-weight: bold; opacity: 0.7; }
    .logo-img { height: 35px; }

    .main-grid { display: flex; gap: 20px; align-items: start; }
    .col-barcode { flex: 0 0 250px; text-align: center; }
    .col-data { flex: 1; font-size: 13px; }
    
    #barcode { width: 100%; height: 60px; }
    .ref-text { font-size: 32px; font-weight: 900; margin-top: 5px; letter-spacing: -1px; }

    .data-table { width: 100%; border-collapse: collapse; }
    .data-table td { padding: 4px 0; border-bottom: 1px dotted #ccc; }
    .label { font-weight: bold; color: #555; width: 80px; }
    
    .details-section { margin-top: 15px; flex-grow: 1; border: 1px solid #111; border-radius: 4px; padding: 8px; position: relative; }
    .details-label { position: absolute; top: -8px; left: 10px; background: #fff; padding: 0 5px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
    .details-content { font-size: 12px; line-height: 1.4; }

    .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 10px; }
    .footer .printed { font-size: 9px; font-style: italic; color: #777; }
    .signature { width: 180px; text-align: center; border-top: 1px solid #000; padding-top: 5px; font-size: 11px; font-weight: bold; }
    
    ul { margin: 5px 0; padding-left: 15px; list-style-type: square; }
    li { margin-bottom: 2px; }

    /* Brother Layout (Sin cambios grandes) */
    <?php else: ?>
    .page-brother { width: 62mm; height: 29mm; padding: 3px; box-sizing: border-box; text-align: center; }
    .ref-small { font-weight: bold; font-size: 22px; }
    #barcode { width: 90%; height: 40px; margin: 0 auto; }
    <?php endif; ?>
</style>
</head>
<body>

<?php if($isZebra): ?>
    <div class="ticket">
        <div class="header">
            <div class="logo-area">
                <?php if($logoBase64): ?>
                    <img src="data:image/png;base64,<?php echo $logoBase64; ?>" class="logo-img">
                <?php else: ?>
                    <div style="font-size:24px; font-weight:900;">MODUL 4</div>
                <?php endif; ?>
            </div>
            <div class="title">
                <h1>PARTE DE ENTRADA</h1>
                <p>CONTROL DE TALLER & SERVICIO TÉCNICO</p>
            </div>
        </div>

        <div class="main-grid">
            <div class="col-barcode">
                <svg id="barcode"></svg>
                <div class="ref-text"><?php echo htmlspecialchars($id); ?></div>
            </div>
            <div class="col-data">
                <table class="data-table">
                    <tr><td class="label">CLIENTE:</td><td><?php echo htmlspecialchars($record['client']); ?></td></tr>
                    <tr><td class="label">TÉCNICO:</td><td><?php echo htmlspecialchars($record['technician']); ?></td></tr>
                    <tr><td class="label">FECHA:</td><td><?php echo date("d/m/Y H:i", strtotime($record['date'])); ?></td></tr>
                </table>
            </div>
        </div>

        <div class="details-section">
            <div class="details-label"><?php echo ($type === 'repair') ? 'Descripción del Problema' : 'Configuración de Equipo'; ?></div>
            <div class="details-content">
                <?php if($type === 'repair'): ?>
                    <?php echo nl2br(htmlspecialchars($record['problem'])); ?>
                <?php else: ?>
                    <ul>
                        <?php foreach($record['components'] as $comp): ?>
                            <li><strong><?php echo htmlspecialchars($comp['component_label']); ?>:</strong> <?php echo htmlspecialchars($comp['component_value']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer">
            <div class="printed">Emitido por TPV MODUL4 - <?php echo date("d/m/Y H:i:s"); ?></div>
            <div class="signature">Firma Conformidad</div>
        </div>
    </div>

<?php else: ?>
    <div class="page-brother">
        <div class="ref-small">REF: <?php echo htmlspecialchars($id); ?></div>
        <svg id="barcode"></svg>
    </div>
<?php endif; ?>

<script>
    JsBarcode("#barcode", "<?php echo addslashes($id); ?>", {
        format: "CODE128",
        displayValue: false,
        height: <?php echo $isZebra ? '60' : '40'; ?>,
        margin: 0
    });
</script>

</body>
</html>
<?php
$html = ob_get_clean();

// --- 5. PRODUCCIÓN PDF ---
$uid = uniqid();
$htmlFile = "/tmp/h_print_$uid.html";
$pdfFile = "/tmp/h_print_$uid.pdf";
file_put_contents($htmlFile, $html);

// Usamos orientación Landscape explícita para la Zebra
$orientation = $isZebra ? "--orientation Landscape " : "";
$cmdPdf = "wkhtmltopdf --enable-javascript --javascript-delay 300 $orientation"
        . "--page-width $w --page-height $h "
        . "--margin-top 0 --margin-bottom 0 --margin-left 0 --margin-right 0 "
        . escapeshellarg($htmlFile) . " "
        . escapeshellarg($pdfFile) . " 2>&1";

exec($cmdPdf, $outPdf, $retPdf);

if ($retPdf !== 0) {
    echo json_encode(['status' => 'error', 'message' => 'Error PDF', 'debug' => $outPdf]);
    @unlink($htmlFile);
    exit;
}

// --- 6. ENVÍO A COLA ---
$dest = ($printer === 'gk420d') ? 'GK420d' : 'QL-570';
$cmdPrint = "lp -d " . escapeshellarg($dest) . " " . escapeshellarg($pdfFile) . " 2>&1";

$allSuccess = true;
$debugOutput = [];
for ($i = 0; $i < $copies; $i++) {
    exec($cmdPrint, $outT, $retT);
    if ($retT !== 0) { $allSuccess = false; $debugOutput = array_merge($debugOutput, $outT); }
}

@unlink($htmlFile);
@unlink($pdfFile);

if ($allSuccess) {
    echo json_encode(['status' => 'success', 'id' => $id, 'printer' => $dest, 'copies' => $copies]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error LP', 'debug' => $debugOutput]);
}