<?php
/**
 * instalador_phpmailer.php
 * 
 * INSTRUCCIONES:
 * 1. Sube este archivo a tu servidor IONOS en la carpeta TimeControl
 * 2. Abre en navegador: http://tudominio.com/instalador_phpmailer.php
 * 3. Haz clic en el botón para instalar
 * 4. Cuando termine, elimina este archivo
 */

$installed = file_exists(__DIR__ . '/vendor/autoload.php');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalador PHPMailer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .status.ok {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .button {
            display: block;
            width: 100%;
            padding: 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            margin-bottom: 10px;
            transition: background 0.3s;
        }
        .button:hover {
            background: #764ba2;
        }
        .button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .info {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            font-size: 14px;
            line-height: 1.6;
            color: #666;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .step {
            margin-bottom: 20px;
        }
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
            font-weight: bold;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        #resultado {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Instalador PHPMailer</h1>
        
        <?php if ($installed): ?>
            <div class="status ok">
                ✓ PHPMailer ya está instalado
            </div>
            <div class="info">
                <p>PHPMailer está correctamente instalado en tu servidor.</p>
                <p>Puedes:</p>
                <ol>
                    <li>Eliminar este archivo (<code>instalador_phpmailer.php</code>)</li>
                    <li>Ir a <a href="enviar_correo.php">enviar_correo.php</a></li>
                    <li>Comenzar a enviar correos masivos</li>
                </ol>
            </div>
            <a href="enviar_correo.php" class="button">↓ Ir a Enviar Correos</a>
        <?php else: ?>
            <div class="status error">
                ✗ PHPMailer no está instalado
            </div>
            
            <div class="info">
                <p>Sigue estos pasos para instalar PHPMailer:</p>
                
                <div class="step">
                    <span class="step-number">1</span>
                    <strong>Haz clic en el botón abajo</strong>
                    <p style="margin-top: 10px; color: #999;">Esto instalará PHPMailer automáticamente</p>
                </div>
                
                <div class="step">
                    <span class="step-number">2</span>
                    <strong>Espera a que termine</strong>
                    <p style="margin-top: 10px; color: #999;">No cierres esta página mientras se instala</p>
                </div>
                
                <div class="step">
                    <span class="step-number">3</span>
                    <strong>Listo</strong>
                    <p style="margin-top: 10px; color: #999;">Cuando termine, elimina este archivo y comienza a enviar correos</p>
                </div>
            </div>
            
            <button class="button" onclick="instalar()" id="btnInstalar">
                ▶ Instalar PHPMailer Ahora
            </button>
            
            <div id="resultado"></div>
        <?php endif; ?>
    </div>
    
    <script>
        function instalar() {
            const btn = document.getElementById('btnInstalar');
            const resultado = document.getElementById('resultado');
            
            btn.disabled = true;
            btn.innerHTML = '<div class="spinner"></div> Instalando...';
            resultado.style.display = 'none';
            
            fetch('instalador_phpmailer.php?install=1', {
                method: 'POST'
            })
            .then(response => response.text())
            .then(data => {
                resultado.innerHTML = data;
                resultado.style.display = 'block';
                btn.style.display = 'none';
            })
            .catch(error => {
                resultado.innerHTML = '<div class="status error">Error: ' + error + '</div>';
                resultado.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = '▶ Instalar PHPMailer Ahora';
            });
        }
    </script>
</body>
</html>

<?php
// Procesar instalación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['install'])) {
    // No podemos ejecutar composer directamente desde PHP
    // Pero podemos mostrar instrucciones alternativas
    
    echo '<div class="status error">';
    echo '⚠ Instalación manual requerida<br><br>';
    echo 'Por favor, sigue uno de estos métodos:';
    echo '</div>';
    
    echo '<div class="info">';
    echo '<strong>Opción A: Por SSH (Recomendado)</strong><br>';
    echo 'Conecta por SSH a tu servidor y ejecuta:<br>';
    echo '<code>cd /home/tu_usuario/public_html/TimeControl<br>';
    echo 'composer require phpmailer/phpmailer</code><br><br>';
    
    echo '<strong>Opción B: Descargar PHPMailer manualmente</strong><br>';
    echo '1. Descarga: <a href="https://github.com/PHPMailer/PHPMailer/releases" target="_blank">github.com/PHPMailer/PHPMailer</a><br>';
    echo '2. Descomprime en tu servidor<br>';
    echo '3. Copia a: /TimeControl/vendor/phpmailer/phpmailer<br><br>';
    
    echo '<strong>Opción C: Contacta a soporte IONOS</strong><br>';
    echo 'Pídeles que instalen Composer en tu cuenta<br>';
    echo '</div>';
    
    exit;
}
?>
