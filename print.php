<?php
/**
 * MODUL 4 - SISTEMA DE IMPRESIÓN DUAL PROFESIONAL (v2.24)
 * Optimizada para Brother QL-570 (Ref) y Zebra GK420d (Full)
 */

header('Content-Type: application/json');

// --- 1. CONFIGURACIÓN INICIAL ---
$host = 'localhost';
$db = 'tpv_db';
$user = 'tecnicos';
$pass = 'Nfa8uku4';
$charset = 'utf8mb4';

// Nombres de impresoras en CUPS
$PRINTER_ZEBRA = 'GK420d';
$PRINTER_BROTHER = 'QL-570';

// --- 2. CAPTURA DE PARÁMETROS ---
$id = $_GET['id'] ?? null;
$manualPrinter = $_GET['printer'] ?? null; 
$manualMode = $_GET['mode'] ?? 'full';     
$manualCopies = $_GET['copies'] ?? 1;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID no proporcionada']);
    exit;
}

// --- 3. CONEXIÓN Y DATOS ---
try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $type = (strpos($id, 'R-') === 0) ? 'repair' : 'creation';
    $record = null;

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

    if (!$record) throw new Exception("Registro no encontrado.");

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

$logoPath = __DIR__ . '/images/m4bannerblack.png';
$logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : "";

// --- 5. FUNCIÓN DE PROCESAMIENTO ---
function processLabel($id, $record, $mode, $targetPrinter, $copies = 1) {
    global $logoBase64, $type;
    
    if ($targetPrinter === 'GK420d') {
        $w = '150mm'; $h = '100mm'; $pageSize = '100x150mm'; $dpi = 203;
    } else {
        $w = '62mm'; $h = '29mm'; $pageSize = '62x29mm'; $dpi = 203;
    }

    ob_start();
    ?>
    <html>
    <head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js'></script>
        <style>
            html, body { margin: 0; padding: 0; background: #fff; width: 100%; height: 100%; overflow: hidden; }
            body { font-family: 'Arial Black', Gadget, sans-serif; color: #000; }
            .ticket { width: 100%; height: 100%; padding: 2mm; box-sizing: border-box; display: flex; flex-direction: column; }
            
            /* MODO REFERENCIA (QL y GK) */
            .mode-ref { text-align: center; justify-content: center; align-items: center; }
            .is-zebra.mode-ref .ref-id { font-size: 90px; font-weight: 900; }
            .is-zebra.mode-ref .client-name { font-size: 38px; font-weight: 900; border-top: 5px solid #000; width: 90%; margin-top: 10px; }
            
            .is-brother.mode-ref .ref-id { font-size: 45px; font-weight: 1000; letter-spacing: -2px; margin-top: -5px; }
            .is-brother.mode-ref .client-name { font-size: 20px; font-weight: 1000; border-top: 2px solid #000; width: 100%; }

            /* MODO INFORME COMPLETO (SOLO GK) */
            .header-banner { width: 100%; height: 18mm; text-align: center; }
            .header-info { display: flex; justify-content: space-between; border-bottom: 5px solid #000; }
            .title-full { font-size: 28px; font-weight: 900; }
            .body-full { display: flex; gap: 5mm; margin-top: 5px; align-items: center; }
            .id-full { font-size: 48px; font-weight: 900; }
            .inf-box { margin-top: 5px; border: 4px solid #000; padding: 10px; flex-grow: 1; border-radius: 5px; position: relative; }
            .inf-tag { position: absolute; top: -12px; left: 15px; background: #fff; padding: 0 5px; font-size: 14px; font-weight: 900; }
            
            .comp-table { width: 100%; border-collapse: collapse; }
            .comp-table td { border: 1px solid #000; padding: 4px; font-size: 15px; font-weight: bold; }
            .comp-header { background: #eee; font-weight: 900; width: 35%; }
        </style>
    </head>
    <body class="mode-<?php echo $mode; ?> <?php echo ($targetPrinter === 'GK420d' ? 'is-zebra' : 'is-brother'); ?>">
        <div class="ticket">
            <?php if ($mode === 'ref'): ?>
                <div class="ref-id"><?php echo $id; ?></div>
                <svg id="barcode"></svg>
                <div class="client-name"><?php echo htmlspecialchars($record['client']); ?></div>
            <?php else: ?>
                <div class="header-banner">
                    <?php if($logoBase64): ?><img src="data:image/png;base64,<?php echo $logoBase64; ?>" style="height:100%; width:auto;"><?php endif; ?>
                </div>
                <div class="header-info">
                    <div class="title-full"><?php echo ($type==='repair' ? 'ORDEN TALLER' : 'SISTEMA NUEVO'); ?></div>
                    <div style="font-size:20px;"><?php echo date("d/m/Y"); ?></div>
                </div>
                <div class="body-full">
                    <div style="text-align:center;">
                        <svg id="barcode" style="width:250px; height:60px;"></svg>
                        <div class="id-full"><?php echo $id; ?></div>
                    </div>
                    <div style="font-size:22px; flex:1;">
                        <b>CLIENTE:</b> <?php echo htmlspecialchars($record['client']); ?><br>
                        <b>TÉCNICO:</b> <?php echo htmlspecialchars($record['technician']); ?>
                    </div>
                </div>
                <div class="inf-box">
                    <div class="inf-tag"><?php echo ($type==='repair' ? 'SINTOMAS' : 'COMPONENTES / SERIE'); ?></div>
                    <?php if($type === 'repair'): ?>
                        <div style="font-size:20px; font-weight:bold;"><?php echo nl2br(htmlspecialchars($record['problem'])); ?></div>
                    <?php else: ?>
                        <table class="comp-table">
                            <?php foreach($record['components'] as $comp): ?>
                                <tr>
                                    <td class="comp-header"><?php echo htmlspecialchars($comp['component_label']); ?></td>
                                    <td><?php echo htmlspecialchars($comp['component_value']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>
                <div style="text-align:right; font-size:10px; margin-top:5px;">v2.24 PRO</div>
            <?php endif; ?>
        </div>
        <script>
            JsBarcode("#barcode", "<?php echo addslashes($id); ?>", {
                format: "CODE128", 
                displayValue: false, 
                height: <?php echo ($mode === 'ref' ? ($targetPrinter === 'GK420d' ? 120 : 50) : 60); ?>,
                width: <?php echo ($targetPrinter === 'GK420d' ? 3 : 2); ?>,
                margin: 0
            });
        </script>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    $pdfFile = "/tmp/p_{$id}_$mode.pdf";
    $htmlFile = "/tmp/p_{$id}_$mode.html";
    file_put_contents($htmlFile, $html);
    
    exec("wkhtmltopdf --dpi $dpi --page-width $w --page-height $h --margin-top 0 --margin-bottom 0 --margin-left 2 --margin-right 2 " . escapeshellarg($htmlFile) . " " . escapeshellarg($pdfFile));
    
    $lpOptions = "-o scaling=100 -o orientation-requested=4 -n $copies -o PageSize=$pageSize";
    exec("lp -d " . escapeshellarg($targetPrinter) . " $lpOptions " . escapeshellarg($pdfFile));
    
    @unlink($htmlFile); @unlink($pdfFile);
    return true;
}

if ($manualPrinter) {
    processLabel($id, $record, $manualMode, $manualPrinter, $manualCopies);
} else {
    if ($type === 'repair') {
        processLabel($id, $record, 'ref', $PRINTER_BROTHER, 1);
        processLabel($id, $record, 'full', $PRINTER_ZEBRA, 1);
    } else {
        processLabel($id, $record, 'ref', $PRINTER_BROTHER, 2);
        processLabel($id, $record, 'full', $PRINTER_ZEBRA, 1);
    }
}

echo json_encode(['status' => 'success', 'v' => '2.24']);