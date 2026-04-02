<?php
/**
 * MODUL4 - Linux Print Proxy for CUPS (v2.8 Full Width)
 * Optimización de espacio para ocupar toda la etiqueta 150x100.
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

// --- 3. PROCESAR LOGO (Base64) ---
$logoPath = __DIR__ . '/images/logo_modul4-6.png';
$logoBase64 = "";
if (file_exists($logoPath)) {
    $logoBase64 = base64_encode(file_get_contents($logoPath));
}

// --- 4. CONFIGURACIÓN PÁGINA ---
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
    body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; color: #000; background: #fff; }
    
    <?php if($isZebra): ?>
    .ticket { width: 150mm; height: 100mm; padding: 10mm; box-sizing: border-box; display: flex; flex-direction: column; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 15px; }
    .header h1 { margin: 0; font-size: 22px; color: #cc0000; text-transform: uppercase; }
    .logo-img { height: 45px; }

    .main { display: flex; gap: 25px; margin-bottom: 10px; }
    .barcode-area { flex: 0 0 280px; text-align: center; }
    #barcode { width: 100%; height: 75px; }
    .ref-text { font-size: 36px; font-weight: 900; margin-top: 5px; }

    .data-area { flex: 1; font-size: 16px; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table td { padding: 6px 0; border-bottom: 1px dotted #999; }
    .lbl { font-weight: bold; width: 90px; color: #444; }
    
    .details { margin-top: 5px; border: 2px solid #000; padding: 12px; flex-grow: 1; position: relative; border-radius: 5px; }
    .details-tag { position: absolute; top: -10px; left: 15px; background: #fff; padding: 0 8px; font-size: 11px; font-weight: 900; text-transform: uppercase; border: 1px solid #000; border-radius: 3px; }
    .details-text { font-size: 14px; line-height: 1.6; color: #000; }

    .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 15px; font-size: 11px; }
    .signature { width: 250px; text-align: center; border-top: 2px solid #000; padding-top: 8px; font-weight: bold; font-size: 13px; }
    
    <?php else: ?>
    .ticket-brother { width: 62mm; height: 29mm; padding: 3px; box-sizing: border-box; text-align: center; }
    #barcode { width: 90%; height: 40px; margin: 0 auto; }
    <?php endif; ?>
</style>
</head>
<body>

<?php if($isZebra): ?>
    <div class="ticket">
        <div class="header">
            <div><?php if($logoBase64): ?><img src="data:image/png;base64,<?php echo $logoBase64; ?>" class="logo-img"><?php endif; ?></div>
            <div style="text-align:right">
                <h1>PARTE DE ENTRADA</h1>
                <div style="font-size:12px; font-weight:bold; opacity:0.8;">MODUL 4 - CONTROL TALLER</div>
            </div>
        </div>

        <div class="main">
            <div class="barcode-area">
                <svg id="barcode"></svg>
                <div class="ref-text"><?php echo htmlspecialchars($id); ?></div>
            </div>
            <div class="data-area">
                <table class="data-table">
                    <tr><td class="lbl">CLIENTE:</td><td style="font-weight:bold; font-size:18px;"><?php echo htmlspecialchars($record['client']); ?></td></tr>
                    <tr><td class="lbl">TÉCNICO:</td><td><?php echo htmlspecialchars($record['technician']); ?></td></tr>
                    <tr><td class="lbl">FECHA:</td><td><?php echo date("d/m/Y H:i", strtotime($record['date'])); ?></td></tr>
                </table>
            </div>
        </div>

        <div class="details">
            <div class="details-tag"><?php echo ($type === 'repair') ? 'Fallo a Revisar' : 'Configuración de Hardware'; ?></div>
            <div class="details-text">
                <?php if($type === 'repair'): ?>
                    <?php echo nl2br(htmlspecialchars($record['problem'])); ?>
                <?php else: ?>
                    <div style="column-count: 2; column-gap: 30px;">
                        <?php foreach($record['components'] as $comp): ?>
                            <div style="margin-bottom:4px; border-bottom: 1px solid #eee;">• <strong><?php echo htmlspecialchars($comp['component_label']); ?>:</strong> <?php echo htmlspecialchars($comp['component_value']); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer">
            <div style="font-style:italic;">Registro generado el <?php echo date("d/m/Y a las H:i"); ?></div>
            <div class="signature">FIRMA CONFORMIDAD CLIENTE</div>
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
        format: "CODE128", displayValue: false, height: 60, margin: 0
    });
</script>

</body>
</html>
<?php
$html = ob_get_clean();

// --- 5. PRODUCCIÓN PDF ---
$uid = uniqid();
$htmlFile = "/tmp/full_print_$uid.html";
$pdfFile = "/tmp/full_print_$uid.pdf";
file_put_contents($htmlFile, $html);

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

// --- 6. ENVÍO A COLA (v2.8 con fit-to-page para maximizar espacio) ---
$dest = ($printer === 'gk420d') ? 'GK420d' : 'QL-570';

// La opción fit-to-page es clave para estirar el diseño hasta los bordes.
$options = ($printer === 'gk420d') ? "-o orientation-requested=4 -o PageSize=Custom.100x150mm -o fit-to-page" : "";

$cmdPrint = "lp -d " . escapeshellarg($dest) . " $options " . escapeshellarg($pdfFile) . " 2>&1";

exec($cmdPrint, $outT, $retT);

@unlink($htmlFile); @unlink($pdfFile);

if ($retT === 0) {
    echo json_encode(['status' => 'success', 'id' => $id, 'printer' => $dest, 'debug' => 'v2.8 OK (fit-to-page)']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error LP', 'debug' => $outT]);
}