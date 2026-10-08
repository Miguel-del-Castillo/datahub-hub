<?php
// public/index.php — Login del HUB.
// Une tres piezas: la interfaz del profesor (LOGIN/index.php),
// la lógica de login.php de supabase-login y el JavaScript que
// interpreta el fragmento de la URL (#access_token=...) de index.php.

session_start();

// Subimos un nivel desde public/ hasta la raíz del HUB y entramos en src/auth/
require_once __DIR__ . '/../src/auth/supabase_client.php';

// URL a la que Supabase devolverá al usuario tras el login con Google.
// Está en una variable para no tener la ruta escrita a mano en el HTML.
// OJO: debe coincidir con la lista de URL permitidas en el panel de Supabase.
// Termina en index.php (no en la carpeta) porque Apache rechaza la carpeta (Forbidden).
$url_retorno = 'http://127.0.0.1/datahub-hub/public/index.php';

// Mensaje de error que se mostrará encima del formulario (null = ninguno)
$mensaje = null;

// ---------- LÓGICA DE LOGIN (viene de login.php) ----------
// Solo actuamos cuando el formulario se ha enviado por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $mensaje = 'El email y la contraseña son obligatorios.';
    } else {
        // Endpoint de login de GoTrue: valida un usuario existente y devuelve tokens
        $respuesta = supabase_auth_request('/token?grant_type=password', [
            'email' => $email,
            'password' => $password,
        ]);

        if ($respuesta['status'] === 200) {
            // Guardamos lo esencial en la sesión para que el resto de páginas
            // puedan comprobar "¿hay alguien logueado?" sin llamar a Supabase.
            $_SESSION['supabase_access_token'] = $respuesta['data']['access_token'];
            $_SESSION['supabase_refresh_token'] = $respuesta['data']['refresh_token'];
            $_SESSION['usuario_email'] = $respuesta['data']['user']['email'];

            // Antes iba a bienvenida.php; ahora el destino es el panel del HUB
            header('Location: dashboard.php');
            exit;
        } else {
            // GoTrue explica el motivo en error_description o msg
            $mensaje = $respuesta['data']['error_description']
                ?? $respuesta['data']['msg']
                ?? 'No se ha podido iniciar sesión.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DATAHUB</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<!-- min-h-screen en lugar de h-screen + overflow-hidden: con el botón de Google y los
     mensajes la tarjeta es más alta y, en pantallas pequeñas, se cortaría -->

<body class="m-0 p-0 bg-gray-900 antialiased flex items-center justify-center min-h-screen w-full">

    <div class="w-full max-w-md bg-gray-800 rounded-lg shadow-2xl border border-gray-700 overflow-hidden relative">

        <!-- Franja naranja superior (estilo del HUB) -->
        <div class="h-1 w-full bg-orange-500"></div>

        <div class="p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold tracking-wider text-gray-100 mb-1">
                    DATA <span class="text-orange-500">HUB</span>
                </h1>
                <p class="text-sm text-gray-400 font-semibold tracking-wide">ACCESO ADMINISTRACIÓN</p>
            </div>

            <!-- Mensajes: los de PHP (login fallido) y los del JavaScript de abajo
                 (enlaces caducados, resultado del login con Google) usan este mismo párrafo -->
            <p id="mensaje" class="text-sm text-center mb-4 <?= $mensaje ? 'text-red-400' : '' ?>"><?= htmlspecialchars($mensaje ?? '') ?></p>

            <!-- El formulario se envía a esta misma página (index.php) -->
            <form action="index.php" method="POST" class="space-y-6">

                <div>
                    <!-- Antes "Usuario": Supabase identifica a las personas por email -->
                    <label for="email" class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input type="email" id="email" name="email" required
                            class="block w-full pl-10 pr-3 py-2.5 bg-gray-900 border border-gray-700 rounded-md text-gray-200 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 transition-colors sm:text-sm"
                            placeholder="tucorreo@ejemplo.com">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-400 mb-1">Contraseña</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input type="password" id="password" name="password" required
                            class="block w-full pl-10 pr-3 py-2.5 bg-gray-900 border border-gray-700 rounded-md text-gray-200 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 transition-colors sm:text-sm"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between space-x-4 pt-2">
                    <button type="reset"
                        class="w-1/3 flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-gray-500 transition-colors">
                        RESET
                    </button>
                    <button type="submit"
                        class="w-2/3 flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-white bg-orange-500 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-orange-500 transition-colors">
                        ACCEDER
                    </button>
                </div>
            </form>

            <!-- Separador entre el login con email y el login social -->
            <div class="flex items-center gap-3 my-6">
                <div class="flex-1 h-px bg-gray-700"></div>
                <span class="text-xs text-gray-500">o</span>
                <div class="flex-1 h-px bg-gray-700"></div>
            </div>

            <!-- Login con Google: la ruta de vuelta sale de $url_retorno (definida arriba) -->
            <a href="<?= SUPABASE_AUTH_URL ?>/authorize?provider=google&redirect_to=<?= urlencode($url_retorno) ?>"
                class="flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-md bg-gray-700 hover:bg-gray-600 text-gray-100 font-medium text-sm transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                </svg>
                Continuar con Google
            </a>

            <div class="mt-8 pt-6 border-t border-gray-700 flex flex-col space-y-3 text-center text-sm">
                <a href="recuperar.php" class="font-medium text-gray-400 hover:text-orange-500 transition-colors">
                    ¿He olvidado mi contraseña?
                </a>
                <a href="registro.php" class="font-medium text-gray-400 hover:text-orange-500 transition-colors">
                    Crear cuenta
                </a>
                <a href="#" class="font-medium text-gray-400 hover:text-orange-500 transition-colors">
                    Contactar con el administrador
                </a>
            </div>
        </div>
    </div>

    <script>
        // ---------- INTERPRETAR EL FRAGMENTO DE LA URL (viene de index.php de supabase-login) ----------
        // GoTrue, cuando redirect_to apunta a una IP local, recorta la ruta y devuelve
        // siempre a la raíz. El fragmento (#access_token=...) SÍ llega intacto porque
        // el navegador nunca lo envía al servidor, así que lo leemos aquí con JavaScript.
        const fragmento = window.location.hash;
        const parametros = new URLSearchParams(fragmento.substring(1));
        const mensajeEl = document.getElementById('mensaje');

        if (fragmento.includes('type=recovery')) {
            // Enlace del correo de recuperación: pasamos el fragmento a restablecer.php
            window.location.replace('restablecer.php' + fragmento);
        } else if (parametros.has('error')) {
            // Enlace caducado o inválido: mostramos el motivo en el mismo párrafo de mensajes
            const descripcion = parametros.get('error_description') || 'El enlace no es válido.';
            const textoLegible = decodeURIComponent(descripcion.replace(/\+/g, ' '));
            mensajeEl.textContent = 'Error: ' + textoLegible;
            mensajeEl.className = 'text-sm text-center mb-4 text-red-400';
        } else if (parametros.has('access_token')) {
            // Login con Google: enviamos el token a PHP para validarlo y crear la sesión
            fetch('callback_google.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        access_token: parametros.get('access_token'),
                        refresh_token: parametros.get('refresh_token')
                    }),
                })
                .then((respuesta) => respuesta.json())
                .then((datos) => {
                    if (datos.ok) {
                        // Antes iba a bienvenida.php; ahora al panel del HUB
                        window.location.replace('dashboard.php');
                    } else {
                        mensajeEl.textContent = 'Error al iniciar sesión con Google: ' + datos.error;
                        mensajeEl.className = 'text-sm text-center mb-4 text-red-400';
                    }
                });
        }
    </script>
</body>

</html>