<?php
/**
 * DEBUG PRINT - Prueba de impresión y diagnóstico
 */
header('Content-Type: text/plain');

$id = isset($_GET['id']) ? $_GET['id'] : 'R-1001';
$printer = isset($_GET['printer']) ? $_GET['printer'] : 'ql570';

echo "--- DIAGNÓSTICO DE IMPRESIÓN ---\n";
echo "ID: $id\n";
echo "Impresora: $printer\n";

// 1. Probar conexión local a BD
echo "\n1. Probando BD...\n";
try {
    $dsn = "mysql:host=localhost;dbname=tpv_db;charset=utf8mb4";
    $pdo = new PDO($dsn, 'tecnicos', 'Nfa8uku4');
    echo "Conexión exitosa a MySQL.\n";
    
    $stmt = $pdo->prepare("SELECT client FROM repairs WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    if($res) {
        echo "Registro encontrado: " . $res['client'] . "\n";
    } else {
        echo "Registro NO encontrado (esto podría fallar la impresión si no existe).\n";
    }
} catch (Exception $e) {
    echo "ERROR BD: " . $e->getMessage() . "\n";
}

// 2. Probar wkhtmltopdf
echo "\n2. Probando wkhtmltopdf...\n";
$out = []; $ret = 0;
exec("wkhtmltopdf --version", $out, $ret);
if($ret === 0) {
    echo "wkhtmltopdf instalado: " . implode("\n", $out) . "\n";
} else {
    echo "ERROR: wkhtmltopdf no encontrado o no ejecutable por www-data.\n";
}

// 3. Probar impresoras CUPS
echo "\n3. Probando impresoras CUPS...\n";
$out_lp = []; $ret_lp = 0;
exec("lpstat -p", $out_lp, $ret_lp);
echo "Estado de impresoras:\n" . implode("\n", $out_lp) . "\n";

echo "\n--- FIN DEL TEST ---\n";
?>
