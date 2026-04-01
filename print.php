<?php
/**
 * MODUL4 - Linux Print Proxy for CUPS (v2.1)
 * Soporta etiquetas pequeñas (QL-570) e informes detallados (GK420d/Zebra)
 */

header('Content-Type: application/json');

// --- CONFIGURACIÓN BD ---
$host = 'localhost';
$db = 'tpv_db';
$user = 'tecnicos';
$pass = 'Nfa8uku4';
$charset = 'utf8mb4';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$printer = isset($_GET['printer']) ? strtolower($_GET['printer']) : 'ql570';
$copies = isset($_GET['copies']) ? (int) $_GET['copies'] : 1;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'No ID provided']);
    exit;
}

// --- CONEXIÓN Y DATOS ---
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
        throw new Exception("Registro no encontrado en BD: $id");
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// --- CONFIGURACIÓN DE PÁGINA ---
$isZebra = ($printer === 'gk420d');
$w = $isZebra ? '100mm' : '62mm';
$h = $isZebra ? '150mm' : '29mm';

// --- GENERACIÓN HTML ---
ob_start();
?>
<html>
<head>
<meta charset='UTF-8'>
<script src='https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js'></script>
<style>
    body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; color: #000; }
    
    /* Layout Zebra GK420d (100x150) */
    <?php if($isZebra): ?>
    .page-zebra { width: 100mm; height: 150mm; padding: 5mm; box-sizing: border-box; }
    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 10px; }
    .logo { font-size: 24px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; }
    .doc-type { font-size: 14px; font-weight: 600; margin-top: 2px; }
    
    .barcode-section { text-align: center; margin: 15px 0; }
    .ref-id { font-size: 28px; font-weight: 800; margin-top: 5px; }
    svg#barcode { width: 100%; height: 60px; }
    
    .data-grid { margin-top: 15px; }
    .data-row { margin-bottom: 8px; border-bottom: 1px dotted #ccc; padding-bottom: 3px; font-size: 14px; }
    .label { font-weight: bold; width: 80px; display: inline-block; }
    
    .details-box { margin-top: 15px; border: 1px solid #000; padding: 8px; min-height: 120px; font-size: 13px; }
    .details-title { font-weight: 700; border-bottom: 1px solid #000; margin-bottom: 5px; padding-bottom: 2px; text-transform: uppercase; font-size: 11px; }
    
    .footer-stamp { margin-top: 25px; display: flex; justify-content: space-between; align-items: flex-end; }
    .signature-box { width: 60%; border-top: 1px solid #000; text-align: center; font-size: 11px; padding-top: 5px; margin-top: 30px; }
    .date-stamp { font-size: 10px; }
    
    ul { padding-left: 15px; margin: 5px 0; }
    li { margin-bottom: 3px; }

    /* Layout Brother QL-570 (62x29) */
    <?php else: ?>
    .page-brother { width: 62mm; height: 29mm; padding: 3px; box-sizing: border-box; text-align: center; position: relative; }
    .date-small { position: absolute; top: 1px; left: 3px; font-size: 8px; }
    .ref-small { font-weight: 800; font-size: 24px; margin-top: 8px; }
    svg#barcode { width: 90%; height: 40px; margin: 0 auto; }
    <?php endif; ?>
</style>
</head>
<body>

<?php if($isZebra): ?>
    <div class="page-zebra">
        <div class="header">
            <div class="logo">MODUL 4</div>
            <div class="doc-type">PARTE DE ENTRADA / CONTROL TALLER</div>
        </div>

        <div class="barcode-section">
            <svg id="barcode"></svg>
            <div class="ref-id"><?php echo htmlspecialchars($id); ?></div>
        </div>

        <div class="data-grid">
            <div class="data-row"><span class="label">CLIENTE:</span> <?php echo htmlspecialchars($record['client']); ?></div>
            <div class="data-row"><span class="label">TÉCNICO:</span> <?php echo htmlspecialchars($record['technician']); ?></div>
            <div class="data-row"><span class="label">FECHA:</span> <?php echo date("d/m/Y H:i", strtotime($record['date'])); ?></div>
        </div>

        <div class="details-box">
            <div class="details-title"><?php echo ($type === 'repair') ? 'DESCRIPCIÓN DEL PROBLEMA' : 'LISTA DE COMPONENTES'; ?></div>
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

        <div class="footer-stamp">
            <div class="signature-box">Firma Conformidad Cliente</div>
            <div class="date-stamp">Impreso: <?php echo date("d/m/Y H:i"); ?></div>
        </div>
    </div>

<?php else: ?>
    <div class="page-brother">
        <div class="date-small"><?php echo date("d/m/Y H:i"); ?></div>
        <div class="ref-small">REF: <?php echo htmlspecialchars($id); ?></div>
        <svg id="barcode"></svg>
    </div>
<?php endif; ?>

<script>
    JsBarcode("#barcode", "<?php echo addslashes($id); ?>", {
        format: "CODE128",
        displayValue: false,
        height: <?php echo $isZebra ? '60' : '40'; ?>,
        margin: 0,
        background: "transparent"
    });
</script>

</body>
</html>
<?php
$html = ob_get_clean();

// --- PROCESAMIENTO Y EJECUCIÓN ---
$uid = uniqid();
$htmlFile = "/tmp/print_$uid.html";
$pdfFile = "/tmp/print_$uid.pdf";
file_put_contents($htmlFile, $html);

// Ajustes wkhtmltopdf
$cmdPdf = "wkhtmltopdf --enable-javascript --javascript-delay 200 "
        . "--margin-top 0 --margin-bottom 0 --margin-left 0 --margin-right 0 "
        . "--page-width $w --page-height $h "
        . escapeshellarg($htmlFile) . " "
        . escapeshellarg($pdfFile);

exec($cmdPdf, $out1, $ret1);

if ($ret1 !== 0) {
    echo json_encode(['status' => 'error', 'message' => 'Error al generar PDF', 'log' => $out1]);
    @unlink($htmlFile);
    exit;
}

// Envío a impresora
$dest = ($printer === 'gk420d') ? 'GK420d' : 'QL-570';
$cmdPrint = "lp -d " . escapeshellarg($dest) . " " . escapeshellarg($pdfFile);

$allSuccess = true;
for ($i = 0; $i < $copies; $i++) {
    exec($cmdPrint, $outT, $retT);
    if ($retT !== 0) $allSuccess = false;
}

@unlink($htmlFile);
@unlink($pdfFile);

if ($allSuccess) {
    echo json_encode(['status' => 'success', 'id' => $id, 'printer' => $dest, 'copies' => $copies]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No se pudo enviar a la cola de impresión']);
}