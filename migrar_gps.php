<?php
/**
 * SCRIPT DE MIGRACIÃ“N â€” ejecutar UNA SOLA VEZ y luego eliminar.
 * AÃ±ade las columnas de coordenadas GPS a la tabla marcaciones.
 */
session_start();
require_once 'config.php';

// Solo accesible si hay sesiÃ³n de dueÃ±o (protecciÃ³n mÃ­nima)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'dueÃ±o') {
    die('Acceso denegado. Debes iniciar sesiÃ³n como dueÃ±o.');
}

$errores = [];
$ok      = [];

$columnas = [
    'lat_entrada' => "ALTER TABLE marcaciones ADD COLUMN lat_entrada DECIMAL(10,8) NULL AFTER salida",
    'lng_entrada' => "ALTER TABLE marcaciones ADD COLUMN lng_entrada DECIMAL(11,8) NULL AFTER lat_entrada",
    'lat_salida'  => "ALTER TABLE marcaciones ADD COLUMN lat_salida  DECIMAL(10,8) NULL AFTER lng_entrada",
    'lng_salida'  => "ALTER TABLE marcaciones ADD COLUMN lng_salida  DECIMAL(11,8) NULL AFTER lat_salida",
];

foreach ($columnas as $nombre => $sql) {
    // Verificar si la columna ya existe antes de intentar crearla
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'marcaciones'
          AND COLUMN_NAME  = ?
    ");
    $check->execute([$nombre]);

    if ((int)$check->fetchColumn() > 0) {
        $ok[] = "Columna <strong>{$nombre}</strong>: ya existÃ­a (sin cambios).";
        continue;
    }

    try {
        $pdo->exec($sql);
        $ok[] = "Columna <strong>{$nombre}</strong>: creada correctamente.";
    } catch (PDOException $e) {
        $errores[] = "Error al crear <strong>{$nombre}</strong>: " . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MigraciÃ³n GPS â€” Control Horario</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 640px; margin: 60px auto; padding: 20px; }
        h1   { font-size: 1.4rem; margin-bottom: 20px; }
        .ok  { background: #f0fff4; border: 1px solid #9ae6b4; color: #276749; padding: 10px 16px; border-radius: 8px; margin-bottom: 8px; }
        .err { background: #fff5f5; border: 1px solid #fc8181; color: #c53030; padding: 10px 16px; border-radius: 8px; margin-bottom: 8px; }
        .warn { background: #fffbeb; border: 1px solid #f6ad55; color: #744210; padding: 14px 16px; border-radius: 8px; margin-top: 24px; font-size: 0.9rem; }
        a    { color: #667eea; }
    </style>
</head>
<body>
    <h1>MigraciÃ³n GPS â€” Tabla <code>marcaciones</code></h1>

    <?php foreach ($ok as $msg): ?>
        <div class="ok">âœ” <?= $msg ?></div>
    <?php endforeach; ?>

    <?php foreach ($errores as $msg): ?>
        <div class="err">âœ– <?= $msg ?></div>
    <?php endforeach; ?>

    <div class="warn">
        âš ï¸ <strong>Importante:</strong> Una vez comprobado que todo es correcto, <strong>elimina este archivo</strong>
        (<code>migrar_gps.php</code>) del servidor para evitar que sea accedido de nuevo.
    </div>

    <p style="margin-top:20px;"><a href="dueÃ±o.php">â† Volver al panel</a></p>
</body>
</html>

