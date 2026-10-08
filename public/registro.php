<?php
// Iniciamos sesión para poder guardar mensajes de error/éxito entre peticiones
session_start();

// Cargamos la función que sabe hablar con la API de GoTrue
// Ruta nueva: public/ -> sube a la raíz del HUB -> src/auth/
require_once __DIR__ . '/../src/auth/supabase_client.php';

// Variable donde guardaremos el mensaje a mostrar al usuario (éxito o error)
$mensaje = null;
$tipo_mensaje = null; // 'error' o 'exito', para poder darle estilo distinto en el HTML

// Solo procesamos el registro si el formulario se envió por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recogemos los datos del formulario.
    // trim() quita espacios accidentales al principio/final del email.
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validación básica en el lado del servidor antes de llamar a Supabase.
    // No es la validación de seguridad definitiva (esa la hace Supabase),
    // pero evita gastar una llamada de red con datos claramente incompletos.
    if ($email === '' || $password === '') {
        $mensaje = 'El email y la contraseña son obligatorios.';
        $tipo_mensaje = 'error';
    } elseif (strlen($password) < 6) {
        // GoTrue exige un mínimo de 6 caracteres por defecto; lo comprobamos
        // antes para dar un mensaje claro sin esperar la respuesta de la API.
        $mensaje = 'La contraseña debe tener al menos 6 caracteres.';
        $tipo_mensaje = 'error';
    } else {
        // Llamamos al endpoint /signup de GoTrue con email y password.
        // Esta es la llamada real que crea el usuario en Supabase.
        $respuesta = supabase_auth_request('/signup', [
            'email' => $email,
            'password' => $password,
        ]);

        // GoTrue responde 200 cuando el registro se crea correctamente
        // (aunque el usuario aún deba confirmar su email, según la config).
        if ($respuesta['status'] === 200) {
            $mensaje = 'Registro correcto. Revisa tu correo para confirmar la cuenta.';
            $tipo_mensaje = 'exito';
        } else {
            // Si algo falla, Supabase suele devolver un campo "msg" o "error_description"
            // explicando el motivo (email ya registrado, formato inválido, etc.)
            $error_api = $respuesta['data']['msg']
                ?? $respuesta['data']['error_description']
                ?? 'No se ha podido completar el registro.';
            $mensaje = $error_api;
            $tipo_mensaje = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta · DATAHUB Auth</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="m-0 p-0 bg-gray-900 antialiased flex items-center justify-center min-h-screen w-full text-gray-100">

    <div class="w-full max-w-md bg-gray-800 rounded-lg shadow-2xl border border-gray-700 border-t-4 border-t-orange-500 p-8">

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold tracking-wide">Crear cuenta</h1>
            <p class="text-xs text-gray-500 mt-1">Regístrate con tu email</p>
        </div>

        <?php if ($mensaje): ?>
            <p class="text-sm text-center mb-4 <?= $tipo_mensaje === 'error' ? 'text-red-400' : 'text-emerald-400' ?>">
                <?= htmlspecialchars($mensaje) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="registro.php" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                <input type="email" id="email" name="email" required
                    class="block w-full px-3 py-2.5 bg-gray-900 border border-gray-700 rounded-md text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 text-sm"
                    placeholder="tucorreo@ejemplo.com">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-400 mb-1">Contraseña</label>
                <input type="password" id="password" name="password" required minlength="6"
                    class="block w-full px-3 py-2.5 bg-gray-900 border border-gray-700 rounded-md text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 text-sm"
                    placeholder="Mínimo 6 caracteres">
            </div>
            <button type="submit"
                class="w-full py-2.5 px-4 rounded-md bg-orange-500 hover:bg-orange-600 text-white font-bold text-sm transition-colors">
                Registrarme
            </button>
        </form>

        <p class="text-center text-xs text-gray-500 mt-6">
            <a href="index.php" class="hover:text-orange-500 transition-colors">&larr; Volver a iniciar sesión</a>
        </p>

    </div>
</body>

</html>