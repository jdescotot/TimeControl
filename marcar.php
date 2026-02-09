<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empleado') {
    header('Location: index.php');
    exit;
}

$empleado_id = $_SESSION['user_id'];
$hoy = date('Y-m-d');
$hora_actual = date('H:i:s');
$accion = $_POST['accion'] ?? '';

if (!in_array($accion, ['entrada', 'salida'])) {
    die('Acción inválida');
}

// Verificar si hoy es día de descanso programado
$stmt_descanso = $pdo->prepare("SELECT id FROM horarios_semanales WHERE empleado_id = ? AND fecha_descanso = ?");
$stmt_descanso->execute([$empleado_id, $hoy]);

if ($stmt_descanso->fetch()) {
    die('No puedes marcar en un día programado como descanso.');
}

try {
    if ($accion === 'entrada') {
        // Verificar que no exista una jornada abierta (entrada sin salida)
        $stmt = $pdo->prepare("
            SELECT id FROM marcaciones 
            WHERE empleado_id = ? AND fecha = ? AND hora_entrada IS NOT NULL AND hora_salida IS NULL
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$empleado_id, $hoy]);
        if ($stmt->fetch()) {
            die('Ya tienes una jornada abierta. Marca salida antes de una nueva entrada.');
        }

        // Insertar nueva marca con entrada
        $stmt = $pdo->prepare("INSERT INTO marcaciones (empleado_id, fecha, hora_entrada) VALUES (?, ?, ?)");
        $stmt->execute([$empleado_id, $hoy, $hora_actual]);
    } 
    elseif ($accion === 'salida') {
        // Buscar registro de hoy sin salida
        $stmt = $pdo->prepare("
            SELECT id FROM marcaciones 
            WHERE empleado_id = ? AND fecha = ? AND hora_entrada IS NOT NULL AND hora_salida IS NULL
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$empleado_id, $hoy]);
        $registro = $stmt->fetch();

        if (!$registro) {
            die('No puedes marcar salida sin tener una jornada abierta.');
        }

        // Actualizar salida
        $stmt = $pdo->prepare("UPDATE marcaciones SET hora_salida = ? WHERE id = ?");
        $stmt->execute([$hora_actual, $registro['id']]);
    }

    header('Location: empleado.php?mensaje=success');
    exit;
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>