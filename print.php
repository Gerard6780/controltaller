<?php
/**
 * MODUL4 - Linux Print Proxy for CUPS (v2.19 Rescue)
 * Corrección de componentes y comando de impresión ultra-compatible.
 * v2.19 PRO.
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

// --- 4. CONFIGURACIÓN PÁGINA (v2.19) ---
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
        width: 100%; height: 100%; 
        padding: 4mm; box-sizing: border-box; 
        display: flex; flex-direction: column; 
        justify-content: space-between; 
    }
    
    .header-banner { width: 100%; text-align: center; height: 22mm; overflow: hidden; margin-bottom: 3mm; }
    .banner-img { width: 100%; height: 100%; object-fit: contain; }

    .meta-row { display: flex; justify-content: space-between; align-items: baseline; border-bottom: 3.5px solid #000; padding-bottom: 4px; }
    .meta-title { font-size: 24px; font-weight: 900; color: #cc0000; text-transform: uppercase; }
    .meta-date { font-size: 15px; font-weight: bold; }

    .main-body { display: flex; gap: 10mm; margin-top: 3mm; }
    .barcode-col { flex: 0 0 65mm; text-align: center; }
    #barcode { width: 100%; height: 65px; }
    .ref-id { font-size: 48px; font-weight: 900; margin-top: 2px; }

    .data-col { flex: 1; font-size: 20px; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table td { padding: 6px 0; border-bottom: 2px dotted #444; }
    .lbl { font-weight: bold; width: 110px; color: #111; }
    
    .details-box { 
        margin-top: 4mm; border: 3px solid #000; padding: 15px; 
        position: relative; border-radius: 6px; flex-grow: 1;
    }
    .details-tag { position: absolute; top: -14px; left: 20px; background: #fff; padding: 0 10px; font-size: 13px; font-weight: 900; text-transform: uppercase; border: 2px solid #000; }
    .details-content { font-size: 17px; line-height: 1.6; color: #000; font-weight: 600; }

    .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 4mm; }
    .signature { width: 320px; text-align: center; border-top: 4px solid #000; padding-top: 6px; font-weight: 900; font-size: 19px; }
    
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
                <div class="meta-title">ENTRADA SERVICIO TÉCNICO v2.19</div>
                <div class="meta-date">REG: <?php echo date("d/m/Y", strtotime($record['date'])); ?> | <?php echo date("H:i"); ?></div>
            </div>
            <div class="main-body">
                <div class="barcode-col">
                    <svg id="barcode"></svg>
                    <div class="ref-id"><?php echo htmlspecialchars($id); ?></div>
                </div>
                <div class="data-col">
                    <table class="data-table">
                        <tr><td class="lbl">CLIENTE:</td><td style="font-weight:900; font-size:24px;"><?php echo htmlspecialchars($record['client']); ?></td></tr>
                        <tr><td class="lbl">TÉCNICO:</td><td><?php echo htmlspecialchars($record['technician']); ?></td></tr>
                        <tr><td class="lbl">CONTR.:</td><td>MODUL 4 TALLER</td></tr>
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
                    <div style="column-count: 2; font-size: 12px; font-weight:bold;">
                        <?php foreach($record['components'] as $comp): ?>
                            <div>• <strong><?php echo htmlspecialchars($comp['component_label']); ?>:</strong> <?php echo htmlspecialchars($comp['component_value']); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="footer">
            <div style="font-size: 12px; font-weight:bold; color:#cc0000;">PRODUCCIÓN v2.19</div>
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
        format: "CODE128", displayValue: false, height: 65, margin: 0
    });
</script>

</body>
</html>
<?php
$html = ob_get_clean();

// --- 5. PRODUCCIÓN PDF ---
$uid = uniqid();
$htmlFile = "/tmp/tf_$uid.html";
$pdfFile = "/tmp/tf_$uid.pdf";
file_put_contents($htmlFile, $html);

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

// --- 6. ENVÍO A COLA (v2.19 Más compatible) ---
$dest = ($printer === 'gk420d') ? 'GK420d' : 'QL-570';
// Hemos cambiado -o fit-to-page por -o scaling=100 para evitar bloqueos del driver.
$options = ($printer === 'gk420d') ? "-o scaling=100 -o orientation-requested=4 -o PageSize=Custom.100x150mm" : "";

$cmdPrint = "lp -d " . escapeshellarg($dest) . " $options " . escapeshellarg($pdfFile) . " 2>&1";

exec($cmdPrint, $outT, $retT);

@unlink($htmlFile); @unlink($pdfFile);

if ($retT === 0) {
    echo json_encode(['status' => 'success', 'id' => $id, 'printer' => $dest, 'debug' => 'v2.19 OK']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error LP', 'debug' => $outT]);
}