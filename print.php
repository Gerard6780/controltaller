<?php
/**
 * MODUL4 - Linux Print Proxy for CUPS (v2.17 Large Format)
 * Diseño Horizontal 150x100 con fuentes aumentadas y DPI 203.
 * v2.17 PRO.
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

// --- 3. PROCESAR BANNER (v2.17) ---
$logoPath = __DIR__ . '/images/m4bannerblack.png';
$logoBase64 = "";
if (file_exists($logoPath)) {
    $logoBase64 = base64_encode(file_get_contents($logoPath));
}

// --- 4. CONFIGURACIÓN PÁGINA (v2.17 Horizontal 150x100) ---
$isZebra = ($printer === 'gk420d');
$w = $isZebra ? '150mm' : '62mm'; 
$h = $isZebra ? '100mm' : '29mm';

ob_start();
?>
<html>
<head>
<meta charset='UTF-8'>
<script src='https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js'></script>
<style>
    html, body { margin: 0; padding: 0; background: #fff; width: 100%; height: 100%; overflow: hidden; }
    body { font-family: 'Segoe UI', Arial, sans-serif; color: #000; }
    
    <?php if($isZebra): ?>
    .ticket { 
        width: 146mm; height: 98mm; padding: 5mm; box-sizing: border-box; 
        display: flex; flex-direction: column; 
        justify-content: space-between; 
        /* v2.17: Diseño horizontal limpio */
    }
    
    .header-banner { width: 100%; text-align: center; height: 22mm; overflow: hidden; margin-bottom: 2mm; }
    .banner-img { width: 100%; height: 100%; object-fit: contain; }

    .meta-row { display: flex; justify-content: space-between; align-items: baseline; border-bottom: 3px solid #000; padding-bottom: 3px; }
    .meta-title { font-size: 22px; font-weight: 900; color: #cc0000; text-transform: uppercase; }
    .meta-date { font-size: 14px; font-weight: bold; }

    .main-body { display: flex; gap: 10mm; margin-top: 2mm; }
    .barcode-col { flex: 0 0 60mm; text-align: center; }
    #barcode { width: 100%; height: 60px; }
    .ref-id { font-size: 44px; font-weight: 900; margin-top: 1px; }

    .data-col { flex: 1; font-size: 18px; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table td { padding: 5px 0; border-bottom: 1.5px dotted #666; }
    .lbl { font-weight: bold; width: 100px; color: #333; }
    
    .details-box { 
        margin-top: 3mm; border: 2.5px solid #000; padding: 12px; 
        position: relative; border-radius: 5px; flex-grow: 1; min-height: 25mm;
    }
    .details-tag { position: absolute; top: -12px; left: 15px; background: #fff; padding: 0 8px; font-size: 11px; font-weight: 900; text-transform: uppercase; border: 1.5px solid #000; }
    .details-content { font-size: 15px; line-height: 1.6; color: #000; font-weight: 500; }

    .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 3mm; }
    .signature { width: 300px; text-align: center; border-top: 3.5px solid #000; padding-top: 5px; font-weight: 900; font-size: 17px; }
    
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
                <div class="meta-title">PARTE DE ENTRADA v2.17</div>
                <div class="meta-date">Fecha: <?php echo date("d/m/Y H:i", strtotime($record['date'])); ?></div>
            </div>
            <div class="main-body">
                <div class="barcode-col">
                    <svg id="barcode"></svg>
                    <div class="ref-id"><?php echo htmlspecialchars($id); ?></div>
                </div>
                <div class="data-col">
                    <table class="data-table">
                        <tr><td class="lbl">CLIENTE:</td><td style="font-weight:900; font-size:22px;"><?php echo htmlspecialchars($record['client']); ?></td></tr>
                        <tr><td class="lbl">TÉCNICO:</td><td><?php echo htmlspecialchars($record['technician']); ?></td></tr>
                        <tr><td class="lbl">HORA IMP:</td><td><?php echo date("d/m/Y H:i"); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="details-box">
            <div class="details-tag">INFORME TÉCNICO</div>
            <div class="details-content">
                <?php if($type === 'repair'): ?>
                    <?php echo nl2br(htmlspecialchars($record['problem'])); ?>
                <?php else: ?>
                    <div style="column-count: 2; font-size: 11px;">
                        <?php foreach($record['components'] as $comp): ?>
                            <div>• <strong><?php echo htmlspecialchars($comp['component_label']); ?>:</strong> <?php echo htmlspecialchars($comp['component_value']); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="footer">
            <div style="font-size: 11px; font-weight:bold;">MODUL 4 TALLER v2.17 LARGE-FORMAT</div>
            <div class="signature">FIRMA CONFORMIDAD CLIENTE</div>
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
        format: "CODE128", displayValue: false, height: 60, margin: 0
    });
</script>

</body>
</html>
<?php
$html = ob_get_clean();

// --- 5. PRODUCCIÓN PDF ---
$uid = uniqid();
$htmlFile = "/tmp/large_$uid.html";
$pdfFile = "/tmp/large_$uid.pdf";
file_put_contents($htmlFile, $html);

// v2.17: Volvemos a 203 DPI y diseño horizontal 150x100
$cmdPdf = "wkhtmltopdf --enable-javascript --javascript-delay 300 "
        . "--viewport-size 1280x800 --disable-smart-shrinking --dpi 203 "
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
// v2.17: Volvemos al flag de orientación que funcionaba mejor con 150x100
$options = ($printer === 'gk420d') ? "-o orientation-requested=4 -o PageSize=Custom.100x150mm" : "";

$cmdPrint = "lp -d " . escapeshellarg($dest) . " $options " . escapeshellarg($pdfFile) . " 2>&1";

exec($cmdPrint, $outT, $retT);

@unlink($htmlFile); @unlink($pdfFile);

if ($retT === 0) {
    echo json_encode(['status' => 'success', 'id' => $id, 'printer' => $dest, 'debug' => 'v2.17 Large-Format OK']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error LP', 'debug' => $outT]);
}