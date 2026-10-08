<?php require_once __DIR__ . '/../src/config/supabase.php'; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña · DATAHUB Auth</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="m-0 p-0 bg-gray-900 antialiased flex items-center justify-center min-h-screen w-full text-gray-100">

    <div class="w-full max-w-md bg-gray-800 rounded-lg shadow-2xl border border-gray-700 border-t-4 border-t-orange-500 p-8">

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold tracking-wide">Nueva contraseña</h1>
            <p class="text-xs text-gray-500 mt-1">Elige una contraseña nueva</p>
        </div>

        <p id="mensaje" class="text-sm text-center mb-4"></p>

        <form id="form-nueva-password" class="space-y-4">
            <div>
                <label for="password" class="block text-sm font-medium text-gray-400 mb-1">Nueva contraseña</label>
                <input type="password" id="password" name="password" required minlength="6"
                    class="block w-full px-3 py-2.5 bg-gray-900 border border-gray-700 rounded-md text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 text-sm"
                    placeholder="Mínimo 6 caracteres">
            </div>
            <button type="submit"
                class="w-full py-2.5 px-4 rounded-md bg-orange-500 hover:bg-orange-600 text-white font-bold text-sm transition-colors">
                Guardar nueva contraseña
            </button>
        </form>

    </div>

    <script>
        const SUPABASE_AUTH_URL = "<?= SUPABASE_AUTH_URL ?>";
        const SUPABASE_PUBLISHABLE_KEY = "<?= SUPABASE_PUBLISHABLE_KEY ?>";

        const fragmento = window.location.hash.substring(1);
        const parametros = new URLSearchParams(fragmento);
        const accessToken = parametros.get('access_token');
        const tipo = parametros.get('type');

        const mensajeEl = document.getElementById('mensaje');
        const formEl = document.getElementById('form-nueva-password');

        if (!accessToken || tipo !== 'recovery') {
            mensajeEl.textContent = 'Enlace inválido o caducado. Solicita uno nuevo desde "Recuperar contraseña".';
            mensajeEl.className = 'text-sm text-center mb-4 text-red-400';
            formEl.classList.add('hidden');
        }

        formEl.addEventListener('submit', async (evento) => {
            evento.preventDefault();

            const nuevaPassword = document.getElementById('password').value;

            try {
                const respuesta = await fetch(SUPABASE_AUTH_URL + '/user', {
                    method: 'PUT',
                    headers: {
                        'apikey': SUPABASE_PUBLISHABLE_KEY,
                        'Authorization': 'Bearer ' + accessToken,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        password: nuevaPassword
                    }),
                });

                const datos = await respuesta.json();

                if (respuesta.ok) {
                    mensajeEl.textContent = 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.';
                    mensajeEl.className = 'text-sm text-center mb-4 text-emerald-400';
                    formEl.classList.add('hidden');
                } else {
                    mensajeEl.textContent = datos.msg || datos.error_description || 'No se ha podido actualizar la contraseña.';
                    mensajeEl.className = 'text-sm text-center mb-4 text-red-400';
                }
            } catch (error) {
                mensajeEl.textContent = 'Error de conexión: ' + error.message;
                mensajeEl.className = 'text-sm text-center mb-4 text-red-400';
            }
        });
    </script>
</body>

</html>