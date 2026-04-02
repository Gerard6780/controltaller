<?php
/**
 * MODUL4 - Linux Print Proxy for CUPS (v2.6 Fixed Rotation)
 * Diseño Horizontal rotado 90° para rollos de 100x150mm
 * Corregido problema de clipping (recorte) al rotar.
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
        throw new Exception("ID no encontrado.");
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// --- 3. PROCESAR LOGO ---
$logoPath = __DIR__ . '/images/logo_modul4-6.png';
$logoBase64 = "";
if (file_exists($logoPath)) {
    $logoBase64 = base64_encode(file_get_contents($logoPath));
}

// --- 4. CONFIGURACIÓN PÁGINA (Físico real 100x150) ---
$isZebra = ($printer === 'gk420d');
$w = $isZebra ? '100mm' : '62mm';
$h = $isZebra ? '150mm' : '29mm';

ob_start();
?>
<html>
<head>
<meta charset='UTF-8'>
<script src='https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js'></script>
<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; color: #111; background: #fff; }
    
    /* ZEBRA ROTATION LOGIC (v2.6 Fixed) */
    <?php if($isZebra): ?>
    .page { 
        width: 100mm; 
        height: 150mm; 
        overflow: hidden; 
        position: relative; 
    }
    
    .ticket-rotated { 
        width: 150mm; 
        height: 100mm; 
        padding: 6mm; 
        box-sizing: border-box;
        position: absolute;
        top: 0;
        left: 0;
        /* Girar 90 grados y desplazar para que encaje en el lienzo de 100x150 */
        transform: rotate(90deg) translateY(-100mm);
        transform-origin: top left;
        display: flex;
        flex-direction: column;
    }

    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 12px; }
    .header h1 { margin: 0; font-size: 18px; color: #cc0000; letter-spacing: 1px; }
    .logo-img { height: 32px; }

    .main { display: flex; gap: 15px; }
    .barcode-area { flex: 0 0 200px; text-align: center; }
    #barcode { width: 100%; height: 60px; }
    .ref-text { font-size: 28px; font-weight: 900; margin-top: 5px; }

    .data-area { flex: 1; font-size: 13px; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table td { padding: 4px 0; border-bottom: 1px dotted #888; }
    .lbl { font-weight: bold; width: 70px; color: #555; }
    
    .details { margin-top: 12px; border: 1.5px solid #000; padding: 8px; flex-grow: 1; position: relative; }
    .details-tag { position: absolute; top: -8px; left: 10px; background: #fff; padding: 0 5px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
    .details-text { font-size: 12px; line-height: 1.4; color: #000; }

    .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 8px; font-size: 9px; }
    .signature { width: 160px; text-align: center; border-top: 1px solid #000; padding-top: 5px; font-weight: bold; font-size: 11px; }

    /* BROTHER LAYOUT */
    <?php else: ?>
    .ticket-brother { width: 62mm; height: 29mm; padding: 3px; box-sizing: border-box; text-align: center; }
    #barcode { width: 90%; height: 40px; margin: 0 auto; }
    <?php endif; ?>
</style>
</head>
<body>

<?php if($isZebra): ?>
    <div class="page">
        <div class="ticket-rotated">
            <div class="header">
                <div><?php if($logoBase64): ?><img src="data:image/png;base64,<?php echo $logoBase64; ?>" class="logo-img"><?php endif; ?></div>
                <div style="text-align:right"><h1>PARTE DE TALLER</h1></div>
            </div>

            <div class="main">
                <div class="barcode-area">
                    <svg id="barcode"></svg>
                    <div class="ref-text"><?php echo htmlspecialchars($id); ?></div>
                </div>
                <div class="data-area">
                    <table class="data-table">
                        <tr><td class="lbl">CLIENTE:</td><td><?php echo htmlspecialchars($record['client']); ?></td></tr>
                        <tr><td class="lbl">TÉCNICO:</td><td><?php echo htmlspecialchars($record['technician']); ?></td></tr>
                        <tr><td class="lbl">FECHA:</td><td><?php echo date("d/m/Y H:i", strtotime($record['date'])); ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="details">
                <div class="details-tag"><?php echo ($type === 'repair') ? 'Fallo Reportado' : 'Componentes'; ?></div>
                <div class="details-text">
                    <?php if($type === 'repair'): ?>
                        <?php echo nl2br(htmlspecialchars($record['problem'])); ?>
                    <?php else: ?>
                        <div style="column-count: 2;">
                            <?php foreach($record['components'] as $comp): ?>
                                <div>• <strong><?php echo htmlspecialchars($comp['component_label']); ?>:</strong> <?php echo htmlspecialchars($comp['component_value']); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="footer">
                <div>Impreso: <?php echo date("d/m/Y H:i"); ?></div>
                <div class="signature">FIRMA CONFORMIDAD</div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="ticket-brother">
        <div style="font-size: 8px;"><?php echo date("d/m/Y H:i"); ?></div>
        <div style="font-weight:900; font-size:22px;">REF: <?php echo htmlspecialchars($id); ?></div>
        <svg id="barcode"></svg>
    </div>
<?php endif; ?>

<script>
    JsBarcode("#barcode", "<?php echo addslashes($id); ?>", {
        format: "CODE128", displayValue: false, height: 50, margin: 0
    });
</script>

</body>
</html>
<?php
$html = ob_get_clean();

// --- 5. PRODUCCIÓN PDF ---
$uid = uniqid();
$htmlFile = "/tmp/frot_$uid.html";
$pdfFile = "/tmp/frot_$uid.pdf";
file_put_contents($htmlFile, $html);

// Sin flags de orientación, rotamos por CSS
$cmdPdf = "wkhtmltopdf --enable-javascript --javascript-delay 300 "
        . "--page-width $w --page-height $h "
        . "--margin-top 0 --margin-bottom 0 --margin-left 0 --margin-right 0 "
        . escapeshellarg($htmlFile) . " "
        . escapeshellarg($pdfFile) . " 2>&1";

exec($cmdPdf, $outPdf, $retPdf);

if ($retPdf !== 0) {
    echo json_encode(['status' => 'error', 'message' => 'Error PDF', 'debug' => $outPdf]);
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

@unlink($htmlFile); @unlink($pdfFile);

if ($allSuccess) {
    echo json_encode(['status' => 'success', 'id' => $id, 'printer' => $dest]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error LP', 'debug' => $debugOutput]);
}