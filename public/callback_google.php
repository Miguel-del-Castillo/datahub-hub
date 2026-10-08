<?php
session_start();

require_once __DIR__ . '/../src/auth/supabase_client.php';

// Esta página no la visita el usuario directamente: la llama nuestro
// JavaScript (fetch) desde index.php, enviando el access_token que
// recibió en el fragmento de la URL tras el login con Google.

// Leemos el cuerpo JSON de la petición (fetch envía JSON, no un $_POST normal)
$cuerpo = json_decode(file_get_contents('php://input'), true);
$accessToken = $cuerpo['access_token'] ?? null;
$refreshToken = $cuerpo['refresh_token'] ?? null;

// Preparamos la respuesta como JSON, ya que quien nos llama es JavaScript, no un navegador humano
header('Content-Type: application/json');

if (!$accessToken) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Falta el access_token.']);
    exit;
}

// Le pedimos a GoTrue los datos del usuario dueño de este token,
// para confirmar que es válido y obtener su email.
$respuesta = supabase_auth_get_user($accessToken);

if ($respuesta['status'] === 200) {
    // Guardamos la sesión igual que hacíamos en login.php con email/password.
    $_SESSION['supabase_access_token'] = $accessToken;
    $_SESSION['supabase_refresh_token'] = $refreshToken;
    $_SESSION['usuario_email'] = $respuesta['data']['email'];

    echo json_encode(['ok' => true, 'email' => $respuesta['data']['email']]);
} else {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Token inválido o caducado.']);
}