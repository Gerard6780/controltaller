<?php
/**
 * MODUL4 - Linux Print Proxy for CUPS (v2.15 Safe Margin)
 * Reducción a 140x95mm para forzar al sensor a detectar el hueco del rollo.
 * v2.15 PRO.
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

// --- 3. PROCESAR BANNER ---
$logoPath = __DIR__ . '/images/m4bannerblack.png';
$logoBase64 = "";
if (file_exists($logoPath)) {
    $logoBase64 = base64_encode(file_get_contents($logoPath));
}

// --- 4. CONFIGURACIÓN PÁGINA (v2.15: 140x95 para garantizar fin de página) ---
$isZebra = ($printer === 'gk420d');
$w = $isZebra ? '140mm' : '62mm'; 
$h = $isZebra ? '95mm' : '29mm';

ob_start();
?>
<html>
<head>
<meta charset='UTF-8'>
<script src='https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js'></script>
<style>
    html, body { margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden; background: #fff; }
    body { font-family: 'Segoe UI', Arial, sans-serif; color: #000; }
    
    <?php if($isZebra): ?>
    .ticket { 
        width: 140mm; height: 95mm; padding: 4mm; box-sizing: border-box; 
        display: flex; flex-direction: column; 
        justify-content: space-between; 
        overflow: hidden;
    }
    
    .header-banner { width: 100%; text-align: center; height: 19mm; overflow: hidden; }
    .banner-img { width: 100%; height: 100%; object-fit: contain; }

    .meta-row { display: flex; justify-content: space-between; align-items: baseline; border-bottom: 2px solid #111; padding-bottom: 2px; }
    .meta-title { font-size: 16px; font-weight: 800; color: #cc0000; text-transform: uppercase; }
    .meta-date { font-size: 10px; font-weight: bold; }

    .main-body { display: flex; gap: 6mm; margin-top: 1mm; }
    .barcode-col { flex: 0 0 50mm; text-align: center; }
    #barcode { width: 100%; height: 50px; }
    .ref-id { font-size: 34px; font-weight: 900; margin-top: 1px; }

    .data-col { flex: 1; font-size: 13px; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table td { padding: 3px 0; border-bottom: 1px dotted #888; }
    .lbl { font-weight: bold; width: 80px; color: #333; }
    
    .details-box { 
        margin-top: 2mm; border: 1.5px solid #111; padding: 8px; 
        position: relative; border-radius: 3px; flex-grow: 1;
    }
    .details-tag { position: absolute; top: -8px; left: 10px; background: #fff; padding: 0 5px; font-size: 9px; font-weight: 900; text-transform: uppercase; border: 1px solid #111; border-radius: 2px; }
    .details-content { font-size: 12px; line-height: 1.4; color: #000; overflow: hidden; }

    .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 2mm; }
    .signature { width: 250px; text-align: center; border-top: 2.5px solid #111; padding-top: 4px; font-weight: 900; font-size: 13px; }
    
    <?php else: ?>
    .ticket-brother { width: 62mm; height: 29mm; padding: 3px; box-sizing: border-box; text-align: center; }
    #barcode { width: 90%; height: 40px; margin: 0 auto; }
    <?php endif; ?>
</style>
</head>
<body>

<?php if($isZebra): ?>
    <div class="ticket">
        <div class="header-container">
            <div class="header-banner">
                <?php if($logoBase64): ?><img src="data:image/png;base64,<?php echo $logoBase64; ?>" class="banner-img"><?php endif; ?>
            </div>
            <div class="meta-row">
                <div class="meta-title">Servicio Técnico v2.15</div>
                <div class="meta-date"><?php echo date("d/m/Y H:i", strtotime($record['date'])); ?></div>
            </div>
            <div class="main-body">
                <div class="barcode-col">
                    <svg id="barcode"></svg>
                    <div class="ref-id"><?php echo htmlspecialchars($id); ?></div>
                </div>
                <div class="data-col">
                    <table class="data-table">
                        <tr><td class="lbl">CLIENTE:</td><td style="font-weight:900;"><?php echo htmlspecialchars($record['client']); ?></td></tr>
                        <tr><td class="lbl">TÉCNICO:</td><td><?php echo htmlspecialchars($record['technician']); ?></td></tr>
                        <tr><td class="lbl">EMITIDO:</td><td><?php echo date("d/m/Y"); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="details-box">
            <div class="details-tag">Notas de Taller</div>
            <div class="details-content">
                <?php if($type === 'repair'): ?>
                    <?php echo nl2br(htmlspecialchars($record['problem'])); ?>
                <?php else: ?>
                    <div style="column-count: 2; font-size: 10px;">
                        <?php foreach($record['components'] as $comp): ?>
                            <div>• <strong><?php echo htmlspecialchars($comp['component_label']); ?>:</strong> <?php echo htmlspecialchars($comp['component_value']); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="footer">
            <div style="font-size: 9px; font-weight:bold;">MODUL 4 TPV v2.15</div>
            <div class="signature">FIRMA CONFORMIDAD</div>
        </div>
    </div>
<?php else: ?>
    <div class="ticket-brother">
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
$htmlFile = "/tmp/safe_$uid.html";
$pdfFile = "/tmp/safe_$uid.pdf";
file_put_contents($htmlFile, $html);

$cmdPdf = "wkhtmltopdf --enable-javascript --javascript-delay 300 "
        . "--viewport-size 1280x800 --disable-smart-shrinking --dpi 300 "
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
$options = ($printer === 'gk420d') ? "-o orientation-requested=4 -o PageSize=Custom.100x150mm" : "";

$cmdPrint = "lp -d " . escapeshellarg($dest) . " $options " . escapeshellarg($pdfFile) . " 2>&1";

exec($cmdPrint, $outT, $retT);

@unlink($htmlFile); @unlink($pdfFile);

if ($retT === 0) {
    echo json_encode(['status' => 'success', 'id' => $id, 'printer' => $dest, 'debug' => 'v2.15 SAFE-MARGIN OK']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error LP', 'debug' => $outT]);
}