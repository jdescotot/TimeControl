<?php
session_start();
require_once 'config.php';

require_dueno();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dueño.php');
    exit;
}

$empleado_id = (int)($_POST['empleado_id'] ?? 0);
$es_gerente = (int)($_POST['es_gerente'] ?? 0) === 1 ? 1 : 0;
$dueno_id = (int)$_SESSION['user_id'];

if ($empleado_id <= 0) {
    header('Location: dueño.php?error=' . urlencode('Empleado inválido'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'empleado' AND propietario_id = ? LIMIT 1");
    $stmt->execute([$empleado_id, $dueno_id]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        header('Location: dueño.php?error=' . urlencode('Empleado no encontrado para este dueño'));
        exit;
    }

    $stmt_update = $pdo->prepare("UPDATE usuarios SET es_gerente = ? WHERE id = ? AND propietario_id = ? AND rol = 'empleado'");
    $stmt_update->execute([$es_gerente, $empleado_id, $dueno_id]);

    header('Location: dueño.php?mensaje=gerente_actualizado');
    exit;
} catch (Exception $e) {
    error_log('Error al actualizar gerente: ' . $e->getMessage());
    header('Location: dueño.php?error=' . urlencode('No se pudo actualizar el permiso de gerente'));
    exit;
}