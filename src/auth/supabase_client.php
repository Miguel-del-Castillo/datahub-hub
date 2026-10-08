<?php

/**
 * Cliente genérico para hablar con la API REST de Auth de Supabase (GoTrue).
 *
 * Todas las llamadas a GoTrue (signup, login, recuperación...) comparten
 * la misma forma: POST + cabeceras fijas + cuerpo JSON. Esta función
 * evita repetir el código de cURL en cada script.
 */

require_once __DIR__ . '/../config/supabase.php';

/**
 * Hace una petición POST a un endpoint de GoTrue y devuelve la respuesta decodificada.
 *
 * @param string $endpoint  Ruta relativa dentro de /auth/v1, ej: "/signup"
 * @param array  $body      Datos a enviar, se codifican como JSON
 * @return array            ['status' => int, 'data' => array|null]
 */
function supabase_auth_request(string $endpoint, array $body): array
{
    // Construimos la URL completa uniendo la base de auth con el endpoint concreto
    $url = SUPABASE_AUTH_URL . $endpoint;

    // Inicializamos la sesión de cURL apuntando a esa URL
    $ch = curl_init($url);

    // Configuramos las opciones de la petición:
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,               // Queremos la respuesta como string, no que se imprima directamente
        CURLOPT_POST           => true,                // Es una petición POST
        CURLOPT_POSTFIELDS     => json_encode($body),  // El cuerpo va en formato JSON
        CURLOPT_HTTPHEADER     => [
            // apikey identifica tu proyecto de Supabase (la publishable key)
            'apikey: ' . SUPABASE_PUBLISHABLE_KEY,
            // Le decimos a GoTrue que el cuerpo que enviamos es JSON
            'Content-Type: application/json',
        ],
    ]);

    // Ejecutamos la petición y guardamos la respuesta cruda (texto JSON)
    $respuesta_cruda = curl_exec($ch);

    // Comprobamos si cURL falló a nivel de red (no de lógica de negocio)
    if ($respuesta_cruda === false) {
        $error = curl_error($ch);
        // Nota: ya no llamamos a curl_close($ch) — desde PHP 8.0 el handle
        // es un objeto CurlHandle que se libera solo al salir de ámbito;
        // la función está deprecada desde PHP 8.5 por no tener efecto.
        return ['status' => 0, 'data' => ['error' => $error]];
    }

    // Recuperamos el código de estado HTTP que devolvió Supabase (200, 400, 422...)
    $codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Decodificamos el JSON de respuesta a un array asociativo de PHP
    $data = json_decode($respuesta_cruda, true);

    return ['status' => $codigo_http, 'data' => $data];
}

/**
 * Pide a GoTrue los datos del usuario autenticado, dado su access_token.
 * Se usa tras el login social, donde el token llega al navegador (no a PHP)
 * y necesitamos validarlo y obtener el email para crear la sesión.
 *
 * @param string $accessToken JWT recibido del fragmento de la URL tras el login social
 * @return array ['status' => int, 'data' => array|null]
 */
function supabase_auth_get_user(string $accessToken): array
{
    $url = SUPABASE_AUTH_URL . '/user';

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET        => true, // Esta vez es GET, no POST
        CURLOPT_HTTPHEADER     => [
            'apikey: ' . SUPABASE_PUBLISHABLE_KEY,
            // Aquí va el token del usuario concreto, no la key genérica del proyecto.
            // Es lo que le dice a GoTrue "quiero los datos de ESTE usuario".
            'Authorization: Bearer ' . $accessToken,
        ],
    ]);

    $respuesta_cruda = curl_exec($ch);
    $codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $data = json_decode($respuesta_cruda, true);

    return ['status' => $codigo_http, 'data' => $data];
}
