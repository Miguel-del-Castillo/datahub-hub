<?php
session_start();

require_once __DIR__ . '/../src/auth/supabase_client.php';

// Si había un access_token guardado, le pedimos a GoTrue que lo revoque.
// Esto invalida el token en el lado de Supabase, no solo en nuestra sesión;
// sin este paso, el token seguiría siendo válido hasta que caduque solo.
if (!empty($_SESSION['supabase_access_token'])) {
    $ch = curl_init(SUPABASE_AUTH_URL . '/logout');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '',
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_PUBLISHABLE_KEY,
            'Authorization: Bearer ' . $_SESSION['supabase_access_token'],
        ],
    ]);
    curl_exec($ch);
    // No comprobamos el resultado: aunque falle la revocación en Supabase,
    // igualmente vamos a destruir la sesión de PHP a continuación, que es
    // lo que de verdad nos impide seguir navegando como si estuviéramos logueados.
}

$_SESSION = [];
session_destroy();

header('Location: index.php');
exit;