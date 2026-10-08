const API_URL = '../api/index.php';

export async function apiCall(action, method = 'GET', data = null) {
    try {
        let url = API_URL;
        let options = {
            method: method,
            headers: { 'Content-Type': 'application/json' }
        };

        if (method === 'GET') {
            url += `?action=${encodeURIComponent(action)}`;
        } else if (method === 'POST') {
            options.body = JSON.stringify({ action: action, data: data });
        }

        const response = await fetch(url, options);
        // 401 = la API nos dice que no hay sesión (caducada o cerrada en otra pestaña).
        // En vez de enseñar un error técnico, llevamos al usuario al login.
        // 'index.php' es una ruta relativa a la página actual (public/dashboard.php).
        if (response.status === 401) {
            window.location.href = 'index.php';
            // Devolvemos un error con el mismo formato que el resto, para que el código
            // que llamó a apiCall no se rompa mientras el navegador cambia de página.
            return { status: 'error', message: 'Sesión caducada. Vuelve a iniciar sesión.' };
        }
        if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);

        return await response.json();
    } catch (error) {
        console.error("Fetch API Error:", error);
        return { status: 'error', message: error.message };
    }
}