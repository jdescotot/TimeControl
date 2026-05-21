<?php
session_start();
require_once 'config.php';

echo "<h1>Diagnóstico de BD</h1>";

// 1. Verificar si la columna es_gerente existe
echo "<h2>1. Columnas en tabla usuarios:</h2>";
$stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'es_gerente'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    echo "<p style='color: green;'><b>✅ Columna es_gerente EXISTE</b></p>";
    echo "<pre>" . print_r($result, true) . "</pre>";
} else {
    echo "<p style='color: red;'><b>❌ Columna es_gerente NO EXISTE - La migración SQL no se ejecutó</b></p>";
    echo "<p>Ejecuta esto en tu BD:</p>";
    echo "<pre>ALTER TABLE usuarios ADD COLUMN es_gerente TINYINT(1) NOT NULL DEFAULT 0 AFTER propietario_id;</pre>";
}

// 2. Verificar sesión actual
echo "<h2>2. Sesión actual:</h2>";
echo "<pre>";
foreach ($_SESSION as $key => $value) {
    echo "$key = " . (is_scalar($value) ? htmlspecialchars((string)$value) : gettype($value)) . "\n";
}
echo "</pre>";

// 3. Intentar SELECT con es_gerente
echo "<h2>3. Prueba de SELECT:</h2>";
try {
    $stmt = $pdo->query("SELECT id, username, es_gerente FROM usuarios LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        echo "<p style='color: green;'><b>✅ Query funcionó</b></p>";
        echo "<pre>" . print_r($result, true) . "</pre>";
    } else {
        echo "<p>No hay usuarios en la BD</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'><b>❌ Error en query: " . htmlspecialchars($e->getMessage()) . "</b></p>";
}

echo "<p><a href='dueño.php'>← Volver al panel</a></p>";
echo "<p><a href='index.php'>← Ir a login</a></p>";
?>
