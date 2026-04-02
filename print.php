<?php
/**
 * MODUL 4 - SISTEMA DE IMPRESIÓN DUAL PROFESIONAL (v2.22)
 * Flujo de trabajo automatizado según el tipo de registro.
 */

header('Content-Type: application/json');

// --- 1. CONFIGURACIÓN INICIAL ---
$host = 'localhost';
$db = 'tpv_db';
$user = 'tecnicos';
$pass = 'Nfa8uku4';
$charset = 'utf8mb4';

// Impresoras Configuradas en CUPS
$PRINTER_ZEBRA = 'GK420d';
$PRINTER_BROTHER = 'QL-570';

// --- 2. CAPTURA DE PARÁMETROS ---
$id = $_GET['id'] ?? null;
$manualPrinter = $_GET['printer'] ?? null; // Para re-impresión individual
$manualMode = $_GET['mode'] ?? 'full';     // 'ref' o 'full'
$manualCopies = $_GET['copies'] ?? 1;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'No se especificó la ID del registro.']);
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

    if (!$record) throw new Exception("Registro no encontrado en la base de datos.");

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// --- 4. CARGAR BANNER (LOGO) ---
$logoPath = __DIR__ . '/images/m4bannerblack.png';
$logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : "";

// --- 5. FUNCIÓN CORE: GENERAR Y ENVIAR A COLA ---
function processLabel($id, $record, $mode, $targetPrinter, $copies = 1) {
    global $logoBase64, $type;
    
    // Configuración según impresora objetivo
    if ($targetPrinter === 'GK420d') {
        $w = '150mm'; $h = '100mm'; $pageSize = '100x150mm'; $dpi = 203;
        $lpOptions = "-o scaling=100 -o orientation-requested=4 -n $copies -o PageSize=$pageSize";
    } else {
        $w = '62mm'; $h = '29mm'; $pageSize = '62x29mm'; $dpi = 203;
        $lpOptions = "-o scaling=100 -n $copies -o PageSize=$pageSize";
    }

    ob_start();
    ?>
    <html>
    <head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js'></script>
        <style>
            html, body { margin: 0; padding: 0; background: #fff; width: 100%; height: 100%; overflow: hidden; }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #000; }
            .ticket { width: 100%; height: 100%; padding: 3mm; box-sizing: border-box; display: flex; flex-direction: column; }
            
            /* ESTILO MODO REFERENCIA (SIN BANNER) */
            .mode-ref { text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center; }
            
            /* Ajustes para Zebra (150x100) */
            .is-zebra.mode-ref .ref-id { font-size: 80px; font-weight: 900; line-height: 1; }
            .is-zebra.mode-ref .barcode-svg { width: 95%; height: 100px; }
            .is-zebra.mode-ref .client-name { font-size: 36px; font-weight: bold; border-top: 4px solid #000; margin-top: 10px; padding-top: 5px; }

            /* Ajustes para Brother (62x29) - Mucho más pequeños */
            .is-brother.mode-ref .ref-id { font-size: 32px; font-weight: 900; line-height: 1; margin: 0; }
            .is-brother.mode-ref .barcode-svg { width: 100%; height: 40px; margin: 2px 0; }
            .is-brother.mode-ref .client-name { font-size: 14px; font-weight: bold; border-top: 1px solid #000; padding-top: 2px; }

            /* ESTILO MODO FULL (INFORME DETALLADO) */
            .header-banner { width: 100%; height: 20mm; text-align: center; margin-bottom: 2mm; }
            .banner-img { width: 100%; height: 100%; object-fit: contain; }
            .header-info { display: flex; justify-content: space-between; border-bottom: 4px solid #000; padding-bottom: 2px; }
            .title-full { font-size: 26px; font-weight: 900; color: #cc0000; letter-spacing: -1px; }
            .date-full { font-size: 18px; font-weight: bold; }
            .body-full { display: flex; gap: 8mm; margin-top: 3mm; align-items: center; }
            .barcode-full { text-align: center; border: 2px solid #ddd; padding: 5px; border-radius: 5px; }
            .id-full { font-size: 44px; font-weight: 900; }
            .details-full { flex: 1; font-size: 22px; font-weight: 600; }
            .details-full table { width: 100%; }
            .inf-box { margin-top: 4mm; border: 4px solid #000; padding: 15px; flex-grow: 1; border-radius: 8px; position: relative; }
            .inf-tag { position: absolute; top: -14px; left: 20px; background: #fff; padding: 0 10px; font-size: 14px; font-weight: 900; border: 3px solid #000; }
            .inf-text { font-size: 18px; line-height: 1.4; font-weight: 600; color: #111; }
            .footer-strip { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 5px; }
        </style>
    </head>
    <body class="mode-<?php echo $mode; ?> <?php echo ($targetPrinter === 'GK420d' ? 'is-zebra' : 'is-brother'); ?>">
        <div class="ticket">
            <?php if ($mode === 'ref'): ?>
                <div style="text-align:center; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center;">
                    <div class="ref-id"><?php echo $id; ?></div>
                    <svg id="barcode" class="barcode-svg"></svg>
                    <div class="client-name"><?php echo htmlspecialchars($record['client']); ?></div>
                </div>
            <?php else: ?>
                <div class="header-banner">
                    <?php if($logoBase64): ?><img src="data:image/png;base64,<?php echo $logoBase64; ?>" class="banner-img"><?php endif; ?>
                </div>
                <div class="header-info">
                    <div class="title-full"><?php echo ($type==='repair' ? 'ORDEN DE REPARACIÓN' : 'SISTEMA A MEDIDA'); ?></div>
                    <div class="date-full"><?php echo date("d/m/Y", strtotime($record['date'])); ?></div>
                </div>
                <div class="body-full">
                    <div class="barcode-full">
                        <svg id="barcode" style="width:100%; height:60px;"></svg>
                        <div class="id-full"><?php echo $id; ?></div>
                    </div>
                    <div class="details-full">
                        <table>
                            <tr><td><b>CLIENTE:</b></td><td><?php echo htmlspecialchars($record['client']); ?></td></tr>
                            <tr><td><b>TÉCNICO:</b></td><td><?php echo htmlspecialchars($record['technician']); ?></td></tr>
                            <tr><td><b>ESTADO:</b></td><td>PENDIENTE</td></tr>
                        </table>
                    </div>
                </div>
                <div class="inf-box">
                    <div class="inf-tag"><?php echo ($type==='repair' ? 'AVERÍA DECLARADA' : 'COMPONENTES Y NÚMEROS DE SERIE'); ?></div>
                    <div class="inf-text">
                        <?php if($type === 'repair'): ?>
                            <?php echo nl2br(htmlspecialchars($record['problem'])); ?>
                        <?php else: ?>
                            <div style="column-count: 2; font-size: 11px;">
                                <?php foreach($record['components'] as $comp): ?>
                                    <div>• <b><?php echo htmlspecialchars($comp['component_label']); ?>:</b> <?php echo htmlspecialchars($comp['component_value']); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="footer-strip">
                    <span style="font-size:11px; font-weight:bold;">M4 TPV System v2.22</span>
                    <div style="width:280px; text-align:center; border-top:4px solid #000; font-weight:900; font-size:18px; padding-top:4px;">FIRMA DE RECEPCIÓN</div>
                </div>
            <?php endif; ?>
        </div>
        <script>
            JsBarcode("#barcode", "<?php echo addslashes($id); ?>", {
                format: "CODE128", 
                displayValue: false, 
                height: <?php echo ($mode === 'ref' ? ($targetPrinter === 'GK420d' ? 100 : 45) : 60); ?>,
                width: <?php echo ($targetPrinter === 'GK420d' ? 3 : 2); ?>,
                margin: 0
            });
        </script>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    $uid = uniqid();
    $htmlFile = "/tmp/print_{$mode}_{$uid}.html";
    $pdfFile = "/tmp/print_{$mode}_{$uid}.pdf";
    file_put_contents($htmlFile, $html);

    $cmdPdf = "wkhtmltopdf --enable-javascript --javascript-delay 300 --dpi $dpi --page-width $w --page-height $h --margin-top 0 --margin-bottom 0 --margin-left 0 --margin-right 0 " . escapeshellarg($htmlFile) . " " . escapeshellarg($pdfFile) . " 2>&1";
    exec($cmdPdf);

    $cmdPrint = "lp -d " . escapeshellarg($targetPrinter) . " $lpOptions " . escapeshellarg($pdfFile) . " 2>&1";
    exec($cmdPrint, $out, $ret);

    @unlink($htmlFile); @unlink($pdfFile);
    return ['status' => $ret === 0, 'printer' => $targetPrinter, 'mode' => $mode];
}

// --- 6. LANZAMIENTO DEL FLUJO ---
$results = [];

if ($manualPrinter) {
    // Escenario A: Re-impresión individual desde el Historial
    $results[] = processLabel($id, $record, $manualMode, $manualPrinter, $manualCopies);
} else {
    // Escenario B: Flujo Automático para Nuevas Altas
    if ($type === 'repair') {
        // Reparación: Un ticket pequeño en Brother y el Informe Grande en Zebra
        $results[] = processLabel($id, $record, 'ref', $PRINTER_BROTHER, 1);
        $results[] = processLabel($id, $record, 'full', $PRINTER_ZEBRA, 1);
    } else {
        // Creación: 2 Referencias en Zebra y 1 Informe con S/N también en Zebra
        $results[] = processLabel($id, $record, 'ref', $PRINTER_ZEBRA, 2);
        $results[] = processLabel($id, $record, 'full', $PRINTER_ZEBRA, 1);
    }
}

echo json_encode(['status' => 'success', 'results' => $results, 'v' => '2.22']);