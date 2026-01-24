<?php
require_once 'config.php';

// Crear usuario de hacienda
$username = 'hacienda';
$password = 'hacienda123'; // Contraseña temporal
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Verificar si ya existe
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
    $check->execute([$username]);
    
    if ($check->fetch()) {
        echo "❌ El usuario 'hacienda' ya existe.<br><br>";
        echo "Puedes iniciar sesión con:<br>";
        echo "Usuario: <strong>hacienda</strong><br>";
        echo "Contraseña: <strong>hacienda123</strong><br><br>";
        echo "<a href='index.php'>Ir al login</a>";
    } else {
        // Crear el usuario
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (username, password, rol, requiere_cambio_password, propietario_id) 
            VALUES (?, ?, 'hacienda', 0, NULL)
        ");
        $stmt->execute([$username, $password_hash]);
        
        echo "✅ Usuario de hacienda creado exitosamente!<br><br>";
        echo "Credenciales de acceso:<br>";
        echo "Usuario: <strong>hacienda</strong><br>";
        echo "Contraseña: <strong>hacienda123</strong><br><br>";
        echo "<a href='index.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>Ir al Login</a>";
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br><br>";
    
    // Si el error es por la columna 'rol', mostrar instrucción SQL
    if (strpos($e->getMessage(), 'rol') !== false) {
        echo "Parece que la columna 'rol' no acepta el valor 'hacienda'.<br><br>";
        echo "Ejecuta este SQL en tu base de datos:<br><br>";
        echo "<pre style='background: #f7fafc; padding: 15px; border-radius: 8px; overflow-x: auto;'>";
        echo "ALTER TABLE usuarios MODIFY COLUMN rol ENUM('dueño', 'empleado', 'hacienda') NOT NULL;";
        echo "</pre>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario Hacienda</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #f7fafc;
        }
        pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
</body>
</html>
