<?php
// Ruta nueva: public/ -> sube a la raíz del HUB -> src/auth/
require_once __DIR__ . '/../src/auth/supabase_client.php';

$mensaje = null;
$tipo_mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $mensaje = 'Introduce tu email.';
        $tipo_mensaje = 'error';
    } else {
        // Endpoint /recover: solo pedimos el email, sin contraseña.
        // GoTrue enviará un correo con un enlace que redirige a la
        // "redirect_to" que le indiquemos (debe estar en la lista
        // de Redirect URLs que configuramos en el panel de Supabase).
        $respuesta = supabase_auth_request('/recover', [
            'email' => $email,
            'options' => [
                'redirect_to' => 'http://127.0.0.1/datahub-hub/public/restablecer.php',
            ],
        ]);

        // Importante: por seguridad, GoTrue responde 200 tanto si el email
        // existe como si no (para no revelar qué emails están registrados).
        // Por eso el mensaje de éxito es genérico, no confirma si el email existe.
        if ($respuesta['status'] === 200) {
            $mensaje = 'Si ese email está registrado, recibirás un correo con instrucciones.';
            $tipo_mensaje = 'exito';
        } else {
            $error_api = $respuesta['data']['msg']
                ?? $respuesta['data']['error_description']
                ?? 'No se ha podido procesar la solicitud.';
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
    <title>Recuperar contraseña · DATAHUB Auth</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="m-0 p-0 bg-gray-900 antialiased flex items-center justify-center min-h-screen w-full text-gray-100">

    <div class="w-full max-w-md bg-gray-800 rounded-lg shadow-2xl border border-gray-700 border-t-4 border-t-orange-500 p-8">

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold tracking-wide">Recuperar contraseña</h1>
            <p class="text-xs text-gray-500 mt-1">Te enviaremos un enlace a tu email</p>
        </div>

        <?php if ($mensaje): ?>
            <p class="text-sm text-center mb-4 <?= $tipo_mensaje === 'error' ? 'text-red-400' : 'text-emerald-400' ?>">
                <?= htmlspecialchars($mensaje) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="recuperar.php" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                <input type="email" id="email" name="email" required
                    class="block w-full px-3 py-2.5 bg-gray-900 border border-gray-700 rounded-md text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 text-sm"
                    placeholder="tucorreo@ejemplo.com">
            </div>
            <button type="submit"
                class="w-full py-2.5 px-4 rounded-md bg-orange-500 hover:bg-orange-600 text-white font-bold text-sm transition-colors">
                Enviar enlace de recuperación
            </button>
        </form>

        <p class="text-center text-xs text-gray-500 mt-6">
            <a href="index.php" class="hover:text-orange-500 transition-colors">&larr; Volver a iniciar sesión</a>
        </p>

    </div>
</body>

</html>