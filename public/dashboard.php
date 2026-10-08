<?php
// public/dashboard.php — Panel principal del HUB.
// Antes era dashboard.html. Un .html no puede ejecutar PHP, así que no podía
// comprobar si había sesión: cualquiera con la URL entraba. Con .php sí.
session_start();

// Si no hay email en la sesión, nadie ha iniciado sesión (ni con email ni con Google).
// Redirigimos al login (index.php) y paramos con exit para que no se envíe nada más.
if (empty($_SESSION['usuario_email'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DataHub Workspace - ReCore Systems</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="m-0 p-0 bg-gray-900 overflow-hidden antialiased">

    <div class="flex h-screen w-full bg-gray-900 text-gray-100 font-sans">

        <!-- SIDEBAR -->
        <aside class="w-64 bg-gray-800 border-r border-gray-700 flex flex-col">
            <div class="p-4 border-b border-gray-700 flex justify-between items-center">
                <h1 class="text-lg font-bold tracking-wider">DATA<span class="text-orange-500">HUB</span></h1>
            </div>

            <div class="p-4">
                <button id="btn-open-new-connection" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded shadow transition-colors">
                    + Nueva Conexión
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto p-2">
                <ul id="sidebar-connections-list" class="space-y-1">
                    <!-- JS inyectará las conexiones aquí -->
                </ul>
            </nav>
            <!-- Usuario conectado y cierre de sesión -->
            <div class="p-4 border-t border-gray-700">
                <p class="text-xs text-gray-500 mb-1">Conectado como</p>
                <!-- htmlspecialchars evita que un email con caracteres especiales rompa el HTML -->
                <p class="text-sm text-orange-500 font-semibold truncate mb-3" title="<?= htmlspecialchars($_SESSION['usuario_email']) ?>">
                    <?= htmlspecialchars($_SESSION['usuario_email']) ?>
                </p>
                <a href="logout.php"
                    class="block w-full text-center py-2 px-4 rounded bg-gray-700 hover:bg-gray-600 text-gray-100 text-sm font-medium transition-colors">
                    Cerrar sesión
                </a>
            </div>
        </aside>

        <!-- ÁREA PRINCIPAL -->
        <main class="flex-1 flex flex-col overflow-hidden">

            <!-- Topbar contextual -->
            <header id="topbar-context" class="h-14 bg-gray-800 border-b border-gray-700 flex items-center px-6 justify-between">
                <div class="text-sm font-medium text-gray-400">
                    <span class="text-gray-500">Esperando selección...</span>
                </div>
            </header>

            <!-- Contenedor central inyectado por JS -->
            <section id="main-workspace" class="flex-1 overflow-auto bg-gray-900 relative">
                <!-- JS inyectará las vistas aquí -->
            </section>
        </main>

    </div>

    <!-- MODAL NUEVA CONEXIÓN -->
    <div id="modal-new-connection" class="hidden fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 transition-opacity">
        <div class="bg-gray-800 rounded-lg shadow-2xl w-full max-w-md border border-gray-700 overflow-hidden relative">
            <div class="h-1 w-full bg-orange-500"></div>
            <div class="flex justify-between items-center p-4 border-b border-gray-700 bg-gray-900">
                <h2 class="text-lg font-bold text-gray-100">Crear Conexión MySQL</h2>
                <button id="btn-close-modal" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="form-new-connection" class="p-6 space-y-4">
                <input type="hidden" name="db_driver" value="mysql">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Nombre (Alias)</label>
                    <input type="text" name="alias" required class="w-full bg-gray-700 text-gray-200 border border-gray-600 rounded p-2 focus:outline-none focus:border-orange-500">
                </div>
                <div class="flex space-x-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-400 mb-1">Host</label>
                        <input type="text" name="host" value="localhost" class="w-full bg-gray-700 text-gray-200 border border-gray-600 rounded p-2 focus:outline-none focus:border-orange-500">
                    </div>
                    <div class="w-24">
                        <label class="block text-sm font-medium text-gray-400 mb-1">Puerto</label>
                        <input type="number" name="port" value="3306" class="w-full bg-gray-700 text-gray-200 border border-gray-600 rounded p-2 focus:outline-none focus:border-orange-500">
                    </div>
                </div>
                <div class="flex space-x-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-400 mb-1">Usuario</label>
                        <input type="text" name="user" required class="w-full bg-gray-700 text-gray-200 border border-gray-600 rounded p-2 focus:outline-none focus:border-orange-500">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-400 mb-1">Contraseña</label>
                        <input type="password" name="password" class="w-full bg-gray-700 text-gray-200 border border-gray-600 rounded p-2 focus:outline-none focus:border-orange-500">
                    </div>
                </div>
                <div class="pt-4 flex justify-end space-x-3 border-t border-gray-700 mt-4">
                    <button type="submit" class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded shadow transition-colors">Conectar al Servidor</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL CRUD DINÁMICO (Generado en base al schema) -->
    <div id="modal-crud" class="hidden fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 transition-opacity">
        <div class="bg-gray-800 rounded-lg shadow-2xl w-full max-w-2xl border border-gray-700 overflow-hidden relative">
            <div class="h-1 w-full bg-orange-500"></div>
            <div class="flex justify-between items-center p-4 border-b border-gray-700 bg-gray-900">
                <h2 id="modal-crud-title" class="text-lg font-bold text-gray-100">Cargando...</h2>
                <button onclick="window.cerrarModalCrud()" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="modal-crud-body" class="p-6"></div>
        </div>
    </div>

    <script type="module" src="js/main.js"></script>
</body>

</html>