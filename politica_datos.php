<?php
session_start();
require_once 'config.php';

// 1. SEGURIDAD: Si no hay usuario logueado, lo mandamos al login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 2. LÓGICA: Cuando el usuario pulsa "Aceptar y Continuar"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aquí redirigimos finalmente a su panel correspondiente
    if ($_SESSION['rol'] === 'dueño') {
        header('Location: dueño.php');
    } else {
        header('Location: empleado.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protección de Datos - Control Horario</title>
    <link rel="stylesheet" href="empleado.css">
    <style>
        .policy-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .policy-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .policy-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #edf2f7;
            padding-bottom: 20px;
        }

        .policy-content {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
            color: #4a5568;
            line-height: 1.6;
            font-size: 0.95rem;
            max-height: 400px;
            overflow-y: auto; /* Permite scroll si el texto es largo */
        }

        .policy-content h3 {
            color: #2d3748;
            margin-top: 0;
        }

        .boe-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #ebf8ff;
            color: #2b6cb0;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 10px;
            border: 1px solid #bee3f8;
            transition: all 0.2s;
        }

        .boe-button:hover {
            background-color: #bee3f8;
        }

        .accept-section {
            text-align: center;
            padding-top: 20px;
        }

        .btn-accept {
            background-color: #48bb78; /* Verde para indicar acción positiva */
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            width: 100%;
            max-width: 300px;
        }

        .btn-accept:hover {
            background-color: #38a169;
        }
    </style>
</head>
<body>
    <div class="policy-container">
        <header class="header" style="margin-bottom: 20px; border-radius: 8px;">
            <div class="header-content">
                <div class="logo">
                    <span>🔐 Cumplimiento Normativo</span>
                </div>
            </div>
        </header>

        <main class="policy-card">
            <div class="policy-header">
                <h2>Política de Protección de Datos</h2>
                <p>Es necesario aceptar los términos para acceder al sistema.</p>
            </div>

            <div class="policy-content">
                <h3>1. Información Básica</h3>
                <p>En cumplimiento de la <strong>Ley Orgánica 3/2018, de 5 de diciembre, de Protección de Datos Personales y garantía de los derechos digitales</strong>, le informamos que sus datos personales serán tratados con la finalidad exclusiva de gestionar el control horario de la jornada laboral, gestión de nóminas y cumplimiento de las obligaciones legales laborales.</p>

                <h3>2. Responsable del Tratamiento</h3>
                <p>Los datos serán gestionados por la administración de la empresa y no serán cedidos a terceros salvo obligación legal.</p>

                <h3>3. Sus Derechos</h3>
                <p>Usted tiene derecho a acceder, rectificar y suprimir los datos, así como a limitar u oponerse al tratamiento de los mismos.</p>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">

                <p>Para consultar el texto legal completo y detallado, puede acceder al Boletín Oficial del Estado:</p>
                
                <a href="https://www.boe.es/eli/es/lo/2018/12/05/3/con" target="_blank" class="boe-button">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    Ver Ley Orgánica 3/2018 (BOE)
                </a>
            </div>

            <div class="accept-section">
                <form method="POST" action="">
                    <p style="margin-bottom: 15px; font-size: 0.9em; color: #718096;">
                        Al hacer clic en el botón inferior, confirmo que he leído y acepto la política de privacidad.
                    </p>
                    <button type="submit" class="btn-accept">
                        Aceptar y Acceder al Sistema
                    </button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>