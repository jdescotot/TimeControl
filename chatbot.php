<?php
session_start();
require_once 'key.php';

// Inicializar el historial con el mensaje del sistema
if (!isset($_SESSION['historial_chat'])) {
    $_SESSION['historial_chat'] = [
        [
            "role" => "system",
            "content" => "You are MiMo, an AI assistant developed by Xiaomi. Today is date: " . date('l, F d, Y') . ". Your knowledge cutoff date is December 2024."
        ]
    ];
}

function obtenerRespuestaDeIA($mensajeUsuario) {
    $url = MIMO_API_URL;

    // Preparar los mensajes (todo el historial)
    $mensajes = $_SESSION['historial_chat'];
    
    // Agregar el nuevo mensaje del usuario
    $nuevoMensajeUsuario = ["role" => "user", "content" => $mensajeUsuario];
    $mensajes[] = $nuevoMensajeUsuario;

    // Configuración de la petición según formato OpenAI
    $data = [
        "model" => "mimo-v2-flash",
        "messages" => $mensajes,
        "max_completion_tokens" => 1024,
        "temperature" => 0.3,
        "top_p" => 0.95,
        "stream" => false
    ];

    $payload = json_encode($data);
    
    // Headers correctos según especificación de Xiaomi
    $headers = [
        'api-key: ' . MIMO_API_KEY,
        'Content-Type: application/json'
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $respuesta = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    // Manejo de errores
    if ($error) {
        return ["error" => "Error de conexión: " . $error];
    }

    if ($httpCode !== 200) {
        return ["error" => "Error HTTP {$httpCode}: " . substr($respuesta, 0, 300)];
    }

    $respuestaJson = json_decode($respuesta, true);
    
    if (!$respuestaJson || !isset($respuestaJson['choices'][0]['message']['content'])) {
        return ["error" => "Formato de respuesta inesperado: " . substr($respuesta, 0, 300)];
    }

    $contenidoRespuesta = $respuestaJson['choices'][0]['message']['content'];

    // Agregar ambos mensajes al historial de la sesión
    $_SESSION['historial_chat'][] = $nuevoMensajeUsuario;
    $_SESSION['historial_chat'][] = [
        "role" => "assistant",
        "content" => $contenidoRespuesta
    ];

    return ["success" => true, "contenido" => $contenidoRespuesta];
}

$respuestaIA = null;
$errorMensaje = null;

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpiar chat
    if (isset($_POST['accion']) && $_POST['accion'] === 'limpiar') {
        $_SESSION['historial_chat'] = [
            [
                "role" => "system",
                "content" => "You are MiMo, an AI assistant developed by Xiaomi. Today is date: " . date('l, F d, Y') . ". Your knowledge cutoff date is December 2024."
            ]
        ];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Enviar mensaje
    $mensajeUsuario = trim($_POST['mensaje'] ?? '');
    
    if (!empty($mensajeUsuario)) {
        $resultado = obtenerRespuestaDeIA($mensajeUsuario);
        
        if (isset($resultado['error'])) {
            $errorMensaje = $resultado['error'];
        } else {
            $respuestaIA = $resultado['contenido'];
        }
    }
}

// Obtener historial para mostrar (sin el mensaje del sistema)
$historial = array_filter($_SESSION['historial_chat'], function($msg) {
    return $msg['role'] !== 'system';
});
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiMo - Asistente de Xiaomi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 900px;
            height: 85vh;
            max-height: 800px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        header {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #ff6b35;
            font-size: 18px;
        }

        h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .subtitle {
            font-size: 13px;
            opacity: 0.9;
            font-weight: 400;
        }

        .clear-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .clear-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .chat-box {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .chat-box::-webkit-scrollbar {
            width: 8px;
        }

        .chat-box::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .chat-box::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 4px;
        }

        .chat-box::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }

        .message-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-wrapper.user {
            flex-direction: row-reverse;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }

        .avatar.user {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .avatar.ai {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
        }

        .message {
            max-width: 70%;
            padding: 14px 18px;
            border-radius: 16px;
            word-wrap: break-word;
            line-height: 1.6;
            font-size: 15px;
        }

        .message.user-msg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.ai-msg {
            background: white;
            color: #2d3748;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .error-msg {
            background: #fed7d7;
            color: #c53030;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 10px;
            border-left: 4px solid #fc8181;
        }

        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #a0aec0;
            text-align: center;
            padding: 40px;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state h2 {
            color: #4a5568;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .empty-state p {
            font-size: 16px;
        }

        .input-area {
            padding: 25px 30px;
            background: white;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .input-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 5px;
            transition: all 0.3s ease;
        }

        .input-wrapper:focus-within {
            border-color: #ff6b35;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .input-wrapper input[type="text"] {
            flex: 1;
            padding: 12px 15px;
            border: none;
            background: transparent;
            font-size: 15px;
            outline: none;
        }

        .send-btn {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }

        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 107, 53, 0.4);
        }

        .send-btn:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .container {
                height: 100vh;
                max-height: none;
                border-radius: 0;
            }

            header {
                padding: 20px;
            }

            h1 {
                font-size: 20px;
            }

            .message {
                max-width: 85%;
            }

            .input-area {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-left">
                <div class="logo">Mi</div>
                <div>
                    <h1>MiMo</h1>
                    <div class="subtitle">Asistente de Xiaomi</div>
                </div>
            </div>
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="accion" value="limpiar">
                <button type="submit" class="clear-btn">
                    <span>🗑️</span>
                    <span>Limpiar Chat</span>
                </button>
            </form>
        </header>

        <div class="chat-box" id="chatBox">
            <?php if ($errorMensaje): ?>
                <div class="error-msg">
                    ⚠️ <?= htmlspecialchars($errorMensaje) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($historial)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">💬</div>
                    <h2>¡Hola! Soy MiMo</h2>
                    <p>Tu asistente inteligente de Xiaomi. ¿En qué puedo ayudarte hoy?</p>
                </div>
            <?php else: ?>
                <?php foreach ($historial as $msg): ?>
                    <div class="message-wrapper <?= $msg['role'] === 'user' ? 'user' : 'ai' ?>">
                        <div class="avatar <?= $msg['role'] === 'user' ? 'user' : 'ai' ?>">
                            <?= $msg['role'] === 'user' ? 'TÚ' : 'Mi' ?>
                        </div>
                        <div class="message <?= $msg['role'] === 'user' ? 'user-msg' : 'ai-msg' ?>">
                            <?= nl2br(htmlspecialchars($msg['content'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form method="POST" class="input-area" id="chatForm">
            <div class="input-wrapper">
                <input 
                    type="text" 
                    name="mensaje" 
                    id="mensajeInput"
                    placeholder="Escribe tu mensaje aquí..." 
                    required 
                    autocomplete="off"
                />
            </div>
            <button type="submit" class="send-btn">Enviar ✈️</button>
        </form>
    </div>

    <script>
        // Auto-scroll al final del chat
        function scrollToBottom() {
            const chatBox = document.getElementById('chatBox');
            chatBox.scrollTop = chatBox.scrollHeight;
        }
        
        scrollToBottom();

        // Focus automático en el input
        document.getElementById('mensajeInput').focus();

        // Enviar con Enter
        document.getElementById('mensajeInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('chatForm').submit();
            }
        });

        // Scroll después de cargar la página
        window.addEventListener('load', scrollToBottom);
    </script>
</body>
</html>