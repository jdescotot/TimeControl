<?php
require_once 'key.php';

function enviarMensaje($mensaje, $archivo = null) {
    $url = 'https://api.mimo.com/chat'; // Reemplaza con la URL correcta de la API de Mimo
    $headers = [
        'Authorization: Bearer ' . MIMO_API_KEY,
        'Content-Type: application/json'
    ];

    $data = ['mensaje' => $mensaje];
    if ($archivo) {
        $data['archivo'] = base64_encode(file_get_contents($archivo['tmp_name']));
        $data['nombre_archivo'] = $archivo['name'];
    }

    $payload = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $respuesta = curl_exec($ch);
    curl_close($ch);

    return $respuesta;
}

$mensaje_usuario = '';
$respuesta_ia = '';
if ($_POST) {
    $mensaje_usuario = $_POST['mensaje'] ?? '';
    $archivo = $_FILES['archivo'] ?? null;
    if (!empty($mensaje_usuario) || $archivo) {
        $respuesta_ia = enviarMensaje($mensaje_usuario, $archivo);
        if ($respuesta_ia === false) {
            $respuesta_ia = 'Error al conectar con la IA.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Chatbot IA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .chat-container {
            width: 100%;
            max-width: 600px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .chat-header {
            background: #0073e6;
            color: white;
            padding: 15px;
            text-align: center;
        }
        .chat-messages {
            padding: 20px;
            height: 400px;
            overflow-y: auto;
            border-bottom: 1px solid #eee;
        }
        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            max-width: 80%;
        }
        .user-message {
            background: #d1e7ff;
            margin-left: auto;
        }
        .bot-message {
            background: #f1f1f1;
        }
        .chat-input {
            display: flex;
            padding: 15px;
            gap: 10px;
        }
        .chat-input input, .chat-input button {
            padding: 10px;
        }
        .chat-input input {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h2>Chat con IA</h2>
        </div>
        <div class="chat-messages">
            <div class="message user-message"><?php echo htmlspecialchars($mensaje_usuario); ?></div>
            <div class="message bot-message"><?php echo htmlspecialchars($respuesta_ia); ?></div>
        </div>
        <form method="POST" enctype="multipart/form-data" class="chat-input">
            <input type="text" name="mensaje" placeholder="Escribe tu mensaje..." required />
            <input type="file" name="archivo" />
            <button type="submit">Enviar</button>
        </form>
    </div>
</body>
</html>