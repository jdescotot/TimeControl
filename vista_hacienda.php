<?php
require_once 'config.php';

try {
    // Obtener TODOS los dueños
    $stmt = $pdo->query("SELECT id, username FROM usuarios WHERE rol = 'dueño' ORDER BY username");
    $duenos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Hacienda</title>
    <link rel="stylesheet" href="empleado.css">
    <style>
        body { background: #f7fafc; padding: 20px; }
        .panel { background: white; border-radius: 16px; padding: 30px; max-width: 1200px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #667eea; margin: 0 0 30px 0; }
        .dueno-card { background: #f7fafc; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .dueno-card h3 { margin: 0 0 15px 0; color: #2d3748; }
        .empleados-list { margin-left: 20px; }
        .empleado-item { padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .btn { padding: 8px 16px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="panel">
        <h1>🏛️ Panel de Hacienda</h1>
        
        <?php if (empty($duenos)): ?>
            <p>No hay dueños registrados.</p>
        <?php else: ?>
            <?php foreach ($duenos as $dueno): ?>
                <?php
                // Obtener empleados de este dueño
                $stmt_emp = $pdo->prepare("SELECT id, username FROM usuarios WHERE rol = 'empleado' AND propietario_id = ? ORDER BY username");
                $stmt_emp->execute([$dueno['id']]);
                $empleados = $stmt_emp->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <div class="dueno-card">
                    <h3>👤 <?php echo htmlspecialchars($dueno['username']); ?></h3>
                    <p><strong>Total Empleados:</strong> <?php echo count($empleados); ?></p>
                    
                    <?php if (!empty($empleados)): ?>
                        <div class="empleados-list">
                            <strong>Empleados:</strong>
                            <?php foreach ($empleados as $emp): ?>
                                <div class="empleado-item">
                                    • <?php echo htmlspecialchars($emp['username']); ?>
                                    <a href="historial_empleado.php?id=<?php echo $emp['id']; ?>" class="btn" style="padding: 4px 10px; font-size: 12px;">Ver Historial</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="reporte_mensual.php?dueno_id=<?php echo $dueno['id']; ?>" class="btn">Ver Reporte Mensual</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
