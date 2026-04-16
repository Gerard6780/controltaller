<?php
/**
 * Generación de Etiquetas e Informes de Impresión
 * Soporta impresoras Brother QL-570 (etiqueta pequeña) y Zebra (etiqueta grande A6).
 */

// Requerimos la conexión centralizada
require_once 'db.php';

// Obtener ID de la referencia
$id = $_GET['id'] ?? null;
if (!$id) {
    die("Error: No se ha especificado una referencia (ID).");
}

// Determinar el tipo (R- o C-)
$type = (strpos(strtoupper($id), 'R-') === 0) ? 'repair' : 'creation';
$record = null;

// Recuperar los datos del registro desde la base de datos
try {
    if ($type === 'repair') {
        $stmt = $pdo->prepare("SELECT * FROM repairs WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM creations WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        if ($record) {
            // Decodificamos el JSON de componentes para las creaciones
            $record['components'] = json_decode($record['components'] ?? '[]', true);
        }
    }
} catch (Exception $e) {
    die("Error al recuperar los datos: " . $e->getMessage());
}

if (!$record) {
    die("Error: Registro no encontrado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Imprimir - <?php echo $id; ?></title>
    <style>
        /* Estilos base para la impresión */
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; }
        
        /* Contenedor de la etiqueta Brother QL-570 (formato pequeño 29x90mm aprox) */
        .label-brother {
            width: 290px;
            height: 90px;
            padding: 5px;
            border: 1px dashed #ccc;
            margin: 10px;
            page-break-after: always;
            overflow: hidden;
        }
        .label-brother h1 { font-size: 24px; margin: 0; border-bottom: 2px solid #000; }
        .label-brother p { font-size: 14px; margin: 2px 0; font-weight: bold; }

        /* Contenedor de la etiqueta Zebra (formato A6 aprox 150x100mm) */
        .label-zebra {
            width: 150mm;
            height: 100mm;
            padding: 10mm;
            border: 2px solid #000;
            margin: 10px;
            page-break-after: always;
            position: relative;
        }
        .zebra-header { display: flex; justify-content: space-between; border-bottom: 4px solid #000; padding-bottom: 5px; margin-bottom: 10px; }
        .zebra-header h1 { font-size: 42px; margin: 0; }
        .zebra-body { font-size: 18px; line-height: 1.4; }
        .zebra-footer { position: absolute; bottom: 10mm; right: 10mm; font-size: 14px; color: #666; }

        .comp-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .comp-table th, .comp-table td { border: 1px solid #000; padding: 5px; text-align: left; font-size: 14px; }
        .comp-header { background-color: #eee; font-weight: bold; }

        @media print {
            .no-print { display: none; }
            .label-brother, .label-zebra { border: none; margin: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="background:#f4f4f4; padding: 20px; text-align: center; border-bottom: 1px solid #ccc;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 18px; cursor: pointer; background: #cc0000; color: #fff; border: none; border-radius: 5px;">
            🖨️ IMPRIMIR ETIQUETAS
        </button>
        <p style="margin-top:10px; color:#666;">Se generarán dos formatos automáticamente (Brother y Zebra).</p>
    </div>

    <!-- FORMATO 1: BROTHER QL-570 (Identificación rápida) -->
    <div class="label-brother">
        <h1><?php echo htmlspecialchars($id); ?></h1>
        <p>CLIENTE: <?php echo htmlspecialchars($record['client']); ?></p>
        <p>FECHA: <?php echo date('d/m/Y', strtotime($record['date'])); ?></p>
    </div>

    <!-- FORMATO 2: ZEBRA (Informe técnico completo) -->
    <div class="label-zebra">
        <div class="zebra-header">
            <div>
                <small style="text-transform: uppercase; font-weight: bold; color: #cc0000;">MODUL4 - Control Taller</small>
                <h1><?php echo htmlspecialchars($id); ?></h1>
            </div>
            <div style="text-align: right;">
                <p style="margin:0; font-weight: bold;"><?php echo date('d/m/Y H:i', strtotime($record['date'])); ?></p>
                <p style="margin:0;"><?php echo strtoupper($type === 'repair' ? 'Reparación' : 'Creación'); ?></p>
            </div>
        </div>

        <div class="zebra-body">
            <p><strong>CLIENTE:</strong> <?php echo htmlspecialchars($record['client']); ?></p>
            <p><strong>TÉCNICO:</strong> <?php echo htmlspecialchars($record['technician']); ?></p>
            
            <?php if ($type === 'repair'): ?>
                <!-- Contenido específico para Reparaciones -->
                <p><strong>ACCESORIOS:</strong> <?php echo htmlspecialchars($record['accessories'] ?: 'Ninguno'); ?></p>
                <div style="margin-top: 15px; border: 1px solid #000; padding: 10px;">
                    <strong style="display: block; text-decoration: underline; margin-bottom: 5px;">PROBLEMA REPORTADO:</strong>
                    <?php echo nl2br(htmlspecialchars($record['problem'])); ?>
                </div>
            <?php else: ?>
                <!-- Contenido específico para Creaciones (Ensamblajes) -->
                <p><strong>LISTA DE COMPONENTES INSTALADOS:</strong></p>
                <table class="comp-table">
                    <thead>
                        <tr>
                            <th class="comp-header">COMPONENTE</th>
                            <th class="comp-header">P/N</th>
                            <th class="comp-header">S/N</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($record['components'] ?? []) as $comp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($comp['label'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($comp['pn'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($comp['sn'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="zebra-footer">
            Generado automáticamente por el Sistema de Gestión de Taller - Gerard Anta
        </div>
    </div>

    <script>
        // Si el usuario lo prefiere, podemos disparar la impresión al cargar
        // window.onload = () => window.print();
    </script>
</body>
</html>