<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empleado') {
    header('Location: index.php');
    exit;
}

function columnas_perfil_usuario(PDO $pdo): array {
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $mapa = [];
    foreach ([
        'nombre' => 'Nombre',
        'apellido' => 'Apellido',
        'telefono' => 'Número de teléfono',
        'correo' => 'Correo electrónico'
    ] as $col => $label) {
        if (in_array($col, $cols, true)) {
            $mapa[$col] = $label;
        }
    }

    if (!isset($mapa['correo']) && in_array('email', $cols, true)) {
        $mapa['email'] = 'Correo electrónico';
    }

    return $mapa;
}

function perfil_incompleto(PDO $pdo, int $usuario_id, array $columnas): bool {
    if (empty($columnas)) {
        return false;
    }

    $sql = 'SELECT ' . implode(', ', $columnas) . ' FROM usuarios WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$datos) {
        return true;
    }

    foreach ($columnas as $col) {
        if (!array_key_exists($col, $datos) || $datos[$col] === null || $datos[$col] === '') {
            return true;
        }
    }

    return false;
}

$usuario_id = (int)$_SESSION['user_id'];
$columnas = columnas_perfil_usuario($pdo);

if (empty($columnas)) {
    header('Location: empleado.php');
    exit;
}

$select_sql = 'SELECT ' . implode(', ', array_keys($columnas)) . ' FROM usuarios WHERE id = ?';
$select_stmt = $pdo->prepare($select_sql);
$select_stmt->execute([$usuario_id]);
$datos = $select_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputs = [];
    foreach (array_keys($columnas) as $col) {
        $inputs[$col] = trim($_POST[$col] ?? '');
    }

    foreach ($inputs as $col => $valor) {
        if ($valor === '') {
            $errores[] = 'El campo ' . $columnas[$col] . ' es obligatorio';
        }
    }

    if (isset($inputs['correo']) && $inputs['correo'] !== '' && !filter_var($inputs['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo válido';
    }

    if (isset($inputs['email']) && $inputs['email'] !== '' && !filter_var($inputs['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo válido';
    }

    if (isset($inputs['telefono']) && $inputs['telefono'] !== '' && !preg_match('/^[0-9+\s-]{7,20}$/', $inputs['telefono'])) {
        $errores[] = 'El teléfono debe tener entre 7 y 20 caracteres';
    }

    if (empty($errores)) {
        $campos_update = [];
        $params = [];
        foreach ($inputs as $col => $valor) {
            $campos_update[] = $col . ' = ?';
            $params[] = $valor;
        }
        $params[] = $usuario_id;

        $update_sql = 'UPDATE usuarios SET ' . implode(', ', $campos_update) . ' WHERE id = ?';
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute($params);

        header('Location: empleado.php?perfil_actualizado=1');
        exit;
    } else {
        $datos = array_merge($datos, $inputs);
    }
}

if (perfil_incompleto($pdo, $usuario_id, array_keys($columnas)) === false && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: empleado.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar datos de seguridad</title>
    <link rel="stylesheet" href="empleado.css">
    <style>
        .perfil-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .field-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            font-weight: 600;
            font-size: 16px;
            margin-right: 12px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .form-field-wrapper {
            display: flex;
            align-items: flex-start;
            margin-bottom: 24px;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-field-content {
            flex: 1;
            position: relative;
        }
        
        .form-field-content label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
        }
        
        .form-field-content input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
            box-sizing: border-box;
        }
        
        .form-field-content input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }
        
        .form-field-content input::placeholder {
            color: #a0aec0;
        }
        
        .field-icon {
            position: absolute;
            right: 16px;
            top: 42px;
            color: #cbd5e0;
            pointer-events: none;
        }
        
        .progress-indicator {
            background: #f7fafc;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            border: 2px solid #e2e8f0;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 10px;
        }
        
        .progress-text {
            font-size: 13px;
            color: #718096;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container perfil-container">
        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Control Horario</span>
                </div>
                <div class="user-info">
                    <span class="welcome-text">Bienvenido,</span>
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="card">
                <div class="card-header">
                    <h2>🔒 Completa tus datos de seguridad</h2>
                </div>
                <div class="card-body">
                    <div class="status-message info" style="margin-bottom: 20px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <span>Por seguridad necesitamos que completes tu información personal.</span>
                    </div>

                    <div class="progress-indicator">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressBar"></div>
                        </div>
                        <div class="progress-text">Completa todos los campos para continuar</div>
                    </div>

                    <?php if (!empty($errores)): ?>
                        <div class="status-message" style="background-color: #fed7d7; color: #c53030; border-left-color: #e53e3e; margin-bottom: 20px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <ul style="margin: 0; padding-left: 18px;">
                                <?php foreach ($errores as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="perfilForm">
                        <?php 
                        $numero = 1;
                        $iconos = [
                            'nombre' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>',
                            'apellido' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>',
                            'telefono' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>',
                            'correo' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>',
                            'email' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>'
                        ];
                        $placeholders = [
                            'nombre' => 'Ej: Juan',
                            'apellido' => 'Ej: Pérez García',
                            'telefono' => 'Ej: +506 8888-8888',
                            'correo' => 'Ej: usuario@ejemplo.com',
                            'email' => 'Ej: usuario@ejemplo.com'
                        ];
                        foreach ($columnas as $col => $label): 
                        ?>
                            <div class="form-field-wrapper" style="animation-delay: <?php echo ($numero - 1) * 0.1; ?>s;">
                                <div class="field-number"><?php echo $numero; ?></div>
                                <div class="form-field-content">
                                    <label for="<?php echo $col; ?>"><?php echo $label; ?></label>
                                    <input
                                        type="<?php echo ($col === 'correo' || $col === 'email') ? 'email' : 'text'; ?>"
                                        name="<?php echo $col; ?>"
                                        id="<?php echo $col; ?>"
                                        value="<?php echo htmlspecialchars($datos[$col] ?? ''); ?>"
                                        placeholder="<?php echo $placeholders[$col] ?? ''; ?>"
                                        required
                                        <?php if ($col === 'telefono'): ?>pattern="[0-9+\s-]{7,20}" title="Ingresa un número de teléfono válido"<?php endif; ?>
                                        oninput="updateProgress()"
                                    >
                                    <svg class="field-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <?php echo $iconos[$col] ?? ''; ?>
                                    </svg>
                                </div>
                            </div>
                        <?php 
                            $numero++;
                        endforeach; 
                        ?>

                        <div style="display: flex; gap: 12px; margin-top: 28px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                Guardar y continuar
                            </button>
                            <a href="logout.php" class="btn btn-secondary" style="flex: 1; text-align: center; display: inline-flex; justify-content: center; align-items: center;">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <footer class="footer">
            <a href="logout.php" class="logout-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Cerrar Sesión
            </a>
        </footer>
    </div>

    <script>
        function updateProgress() {
            const form = document.getElementById('perfilForm');
            const inputs = form.querySelectorAll('input[required]');
            let filled = 0;
            
            inputs.forEach(input => {
                if (input.value.trim() !== '') {
                    filled++;
                }
            });
            
            const percentage = (filled / inputs.length) * 100;
            document.getElementById('progressBar').style.width = percentage + '%';
        }
        
        // Inicializar progreso al cargar
        document.addEventListener('DOMContentLoaded', updateProgress);
    </script>
</body>
</html>
