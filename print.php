<?php
/**
 * MODUL4 - Linux Print Proxy for CUPS
 * Label con REF + código de barras corregido
 */

header('Content-Type: application/json');

$id = isset($_GET['id']) ? $_GET['id'] : null;
$copies = isset($_GET['copies']) ? (int) $_GET['copies'] : 1;
if ($copies < 1) {
    $copies = 1;
}

if (!$id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No ID provided'
    ]);
    exit;
}

// Normalizar referencia (IMPORTANTE para barcode)
$barcodeValue = strtoupper($id);

// ID único para temporales
$uid = uniqid();
$htmlFile = "/tmp/label_$uid.html";
$pdfFile = "/tmp/label_$uid.pdf";

// HTML
$html = "<html>
<head>
<meta charset='UTF-8'>
<script src='https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js'></script>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 4px;
    }
    .date {
        position: absolute;
        top: 2px;
        left: 4px;
        font-size: 9px;
    }
    .container {
        text-align: center;
        margin-top: 10px;
    }
    .ref {
        font-weight: bold;
        font-size: 22px;
        margin-bottom: 4px;
    }
    svg {
        width: 100%;
        height: 40px;
    }
</style>
</head>
<body>

<div class='date'>" . date("d/m/Y H:i") . "</div>

<div class='container'>
    <div class='ref'>REF: " . htmlspecialchars($id) . "</div>
    <svg id='barcode'></svg>
</div>

<script>
    JsBarcode('#barcode', '" . addslashes($barcodeValue) . "', {
        format: 'CODE128',
        displayValue: true,
        fontSize: 10,
        height: 40,
        margin: 0
    });
</script>

</body>
</html>";

// Guardar HTML
file_put_contents($htmlFile, $html);

// Generar PDF (con JS habilitado)
$cmdPdf = "wkhtmltopdf "
    . "--enable-javascript --javascript-delay 200 "
    . "--margin-top 0 --margin-bottom 0 --margin-left 0 --margin-right 0 "
    . "--page-width 62mm --page-height 29mm "
    . escapeshellarg($htmlFile) . " "
    . escapeshellarg($pdfFile);

exec($cmdPdf, $out1, $ret1);

if ($ret1 !== 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to generate PDF',
        'debug' => $out1
    ]);
    exit;
}

// Imprimir
$cmdPrint = "lp -d QL-570 " . escapeshellarg($pdfFile);
$allSuccess = true;
$allOutput = [];

for ($i = 0; $i < $copies; $i++) {
    exec($cmdPrint, $outTemp, $retTemp);
    if ($retTemp !== 0) {
        $allSuccess = false;
        $allOutput[] = $outTemp;
    }
}

// Limpiar temporales
unlink($htmlFile);
unlink($pdfFile);

if ($allSuccess) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Print job sent successfully',
        'id' => $id,
        'copies' => $copies
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to send one or more print jobs',
        'output' => $allOutput,
        'copies' => $copies
    ]);
}
?>