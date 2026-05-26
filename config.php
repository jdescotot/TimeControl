<?php
$host = 'db5019247411.hosting-data.io'; 
$dbname = 'dbs15099139';              
$username = 'dbu5641171';               
$password = 'Hostur.1710';              

// Clave compartida para acceso al panel maestro (hacienda.php)
if (!defined('PANEL_MAESTRO_PASSWORD')) {
    define('PANEL_MAESTRO_PASSWORD', 'Hostur.1710');
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Conexión fallida: " . $e->getMessage());
}

function es_dueno(): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'dueño';
}

function es_gerente(): bool {
    return isset($_SESSION['rol'], $_SESSION['es_gerente'])
        && $_SESSION['rol'] === 'empleado'
        && (int)$_SESSION['es_gerente'] === 1;
}

function es_dueno_o_gerente(): bool {
    return es_dueno() || es_gerente();
}

function owner_scope_id(PDO $pdo): int {
    if (!isset($_SESSION['user_id'])) {
        return 0;
    }

    if (es_dueno()) {
        return (int)$_SESSION['user_id'];
    }

    if (es_gerente()) {
        if (!empty($_SESSION['propietario_id'])) {
            return (int)$_SESSION['propietario_id'];
        }

        $stmt = $pdo->prepare("SELECT propietario_id FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row || empty($row['propietario_id'])) {
            return 0;
        }
        
        $propietario_id = (int)$row['propietario_id'];
        $_SESSION['propietario_id'] = $propietario_id;
        return $propietario_id;
    }

    return 0;
}

function require_dueno_o_gerente(PDO $pdo): int {
    if (!es_dueno_o_gerente()) {
        header('Location: index.php');
        exit;
    }

    $owner_id = owner_scope_id($pdo);
    if ($owner_id <= 0) {
        header('Location: index.php');
        exit;
    }

    return $owner_id;
}

function require_dueno(): void {
    if (!es_dueno()) {
        header('Location: index.php');
        exit;
    }
}

function panel_home_url(): string {
    if (es_dueno()) {
        return 'dueño.php';
    }

    if (es_gerente()) {
        return 'gerente.php';
    }

    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'empleado') {
        return 'empleado.php';
    }

    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'hacienda') {
        return 'hacienda.php';
    }

    return 'index.php';
}
?>