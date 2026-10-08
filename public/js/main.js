import { apiCall } from './api.js';

document.addEventListener('DOMContentLoaded', () => {
    
    // --- ESTADO GLOBAL ---
    let conexionesActivas = []; 
    let conexionSeleccionada = null;

    // --- DOM ELEMENTS ---
    const sidebarList = document.getElementById('sidebar-connections-list');
    const workspace = document.getElementById('main-workspace');
    const topbarContext = document.getElementById('topbar-context');
    const formNewConnection = document.getElementById('form-new-connection');
    const modalNewConnection = document.getElementById('modal-new-connection');

    // --- VISTAS (Template Literals) ---
    const views = {
        dashboard: (conexiones) => `
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-100 mb-6 border-b border-gray-700 pb-2">Panel Principal</h2>
                ${conexiones.length === 0 ? 
                    `<p class="text-gray-400">Sin conexiones activas. Añade un servidor desde el panel lateral.</p>` : 
                    `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        ${conexiones.map(conn => `
                            <div class="bg-gray-800 rounded p-5 border border-gray-700 shadow hover:border-orange-500 cursor-pointer transition-colors" onclick="seleccionarConexion(${conn.id})">
                                <div class="flex items-center mb-2">
                                    <span class="w-3 h-3 rounded-full bg-green-500 mr-3 shadow-[0_0_8px_rgba(34,197,94,0.8)]"></span>
                                    <h3 class="text-lg font-bold text-white">${conn.alias}</h3>
                                </div>
                                <p class="text-xs text-gray-400 font-mono">${conn.user}@${conn.host}</p>
                            </div>
                        `).join('')}
                    </div>`
                }
            </div>
        `,

        serverDetails: (conn) => `
            <div class="p-8">
                <h3 class="text-lg font-bold text-gray-300 mb-4">Esquemas en el Servidor</h3>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    ${conn.databases.map(db => `
                        <div class="bg-gray-800 rounded border border-gray-700 overflow-hidden">
                            <div class="bg-gray-750 px-4 py-2 border-b border-gray-700 font-bold text-orange-500 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                                ${db.name}
                            </div>
                            <div class="p-4 flex flex-wrap gap-2">
                                ${db.tables.length === 0 ? '<span class="text-gray-500 text-xs">Sin tablas</span>' : 
                                    db.tables.map(table => `
                                    <span onclick="window.abrirFormularioInsercion('${db.name}', '${table}')" class="bg-gray-900 border border-gray-700 text-gray-300 text-xs px-2 py-1 rounded hover:border-orange-500 hover:text-orange-500 cursor-pointer transition-colors" title="Clic para insertar en ${table}">${table}</span>
                                `).join('')}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `,
        // Aquí añadiremos la vista del CRUD y la vista del Gestor (SQL Crudo) en próximas iteraciones
    };

    // --- CONTROL DE RENDERIZADO ---
    const renderSidebar = () => {
        sidebarList.innerHTML = conexionesActivas.map(conn => `
            <li class="relative group" data-id="${conn.id}">
                <div class="flex items-center w-full p-1 rounded ${conn.id === conexionSeleccionada?.id ? 'bg-gray-800' : 'hover:bg-gray-700'} transition-colors">
                    <button class="btn-select-connection flex-1 flex items-center justify-between p-1 text-left text-gray-100">
                        <span class="flex items-center">
                            <span class="w-2 h-2 rounded-full bg-green-500 mr-2 shadow-[0_0_8px_rgba(34,197,94,0.8)]"></span>
                            <span class="${conn.id === conexionSeleccionada?.id ? 'text-orange-500 font-bold' : ''}">${conn.alias}</span>
                        </span>
                        <svg class="w-4 h-4 text-gray-400 ${conn.id === conexionSeleccionada?.id ? 'transform rotate-90' : ''} transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>
                <!-- Submenú -->
                <ul class="${conn.id === conexionSeleccionada?.id ? 'block' : 'hidden'} pl-6 mt-1 space-y-1 text-xs font-semibold tracking-wide text-gray-400">
                    <li data-view="arquitectura" class="hover:text-orange-500 cursor-pointer p-1.5 rounded transition-colors">ARQUITECTURA</li>
                    <li data-view="crud" class="hover:text-orange-500 cursor-pointer p-1.5 rounded transition-colors">ACCIONES CRUD</li>
                    <li data-view="gestor" class="hover:text-orange-500 cursor-pointer p-1.5 rounded transition-colors">GESTOR INFORMACIÓN</li>
                </ul>
            </li>
        `).join('');
    };

    window.seleccionarConexion = (id) => {
        conexionSeleccionada = conexionesActivas.find(c => c.id === id);
        renderSidebar();
        workspace.innerHTML = views.serverDetails(conexionSeleccionada);
        topbarContext.innerHTML = `<span class="text-orange-500 font-bold">${conexionSeleccionada.alias}</span> <span class="mx-2 text-gray-600">/</span> <span class="text-gray-300">Resumen Servidor</span>`;
    };

    // --- EVENTOS DEL MENÚ LATERAL ---
    sidebarList.addEventListener('click', (e) => {
        const li = e.target.closest('li[data-id]');
        if (!li) return;
        
        if (e.target.closest('.btn-select-connection')) {
            window.seleccionarConexion(parseInt(li.getAttribute('data-id')));
        }
    });

    // --- MODALES (NUEVA CONEXIÓN) ---
    document.getElementById('btn-open-new-connection').addEventListener('click', () => modalNewConnection.classList.remove('hidden'));
    document.getElementById('btn-close-modal').addEventListener('click', () => modalNewConnection.classList.add('hidden'));

    formNewConnection.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(formNewConnection).entries());
        const submitBtn = formNewConnection.querySelector('button[type="submit"]');
        
        submitBtn.innerText = "Conectando y Extrayendo...";
        submitBtn.disabled = true;

        const result = await apiCall('saveConnection', 'POST', data);
        
        if (result.status === 'success') {
            conexionesActivas.push(result.data);
            modalNewConnection.classList.add('hidden');
            formNewConnection.reset();
            window.seleccionarConexion(result.data.id);
        } else {
            alert("Error: " + result.message);
        }

        submitBtn.innerText = "Conectar al Servidor";
        submitBtn.disabled = false;
    });

    // --- SISTEMA CRUD DINÁMICO ---
    window.cerrarModalCrud = () => document.getElementById('modal-crud').classList.add('hidden');

    function getHtmlInputType(sqlType) {
        const t = sqlType.toLowerCase();
        if (t.includes('int') || t.includes('float') || t.includes('double')) return 'number';
        if (t.includes('date') || t.includes('timestamp')) return 'date';
        if (t.includes('text')) return 'textarea';
        return 'text';
    }

    function renderDynamicForm(columns, dbName, tableName) {
        let html = `<form id="form-crud-insert" class="space-y-4">
                        <input type="hidden" name="target_db" value="${dbName}">
                        <input type="hidden" name="target_table" value="${tableName}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">`;
        
        columns.forEach(col => {
            if (col.Extra === 'auto_increment') return; // Ignorar PKs autoincrementales
            const inputType = getHtmlInputType(col.Type);
            const required = col.Null === 'NO' ? 'required' : '';
            const isTextarea = inputType === 'textarea';
            
            html += `<div class="${isTextarea ? 'md:col-span-2' : ''}">
                        <label class="block text-sm font-medium text-gray-400 mb-1">${col.Field} ${required ? '<span class="text-orange-500">*</span>' : ''}</label>
                        ${isTextarea 
                            ? `<textarea name="${col.Field}" ${required} class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded p-2 focus:border-orange-500 focus:outline-none"></textarea>`
                            : `<input type="${inputType}" name="${col.Field}" ${required} class="w-full bg-gray-900 border border-gray-700 text-gray-200 rounded p-2 focus:border-orange-500 focus:outline-none">`
                        }
                     </div>`;
        });

        html += `</div>
                 <div class="pt-4 flex justify-end space-x-3 border-t border-gray-700 mt-6">
                    <button type="submit" class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded">Insertar en ${tableName}</button>
                 </div></form>`;
        return html;
    }

    window.abrirFormularioInsercion = async (dbName, tableName) => {
        const modal = document.getElementById('modal-crud');
        const modalBody = document.getElementById('modal-crud-body');
        document.getElementById('modal-crud-title').innerText = `Insertar en [${dbName}].[${tableName}]`;
        modalBody.innerHTML = `<p class="text-center py-4 text-orange-500">Analizando esquema desde MySQL...</p>`;
        modal.classList.remove('hidden');

        // Pedir las columnas a PHP (Pasamos los credenciales de la conexión activa)
        const payload = {
            host: conexionSeleccionada.host, user: conexionSeleccionada.user, 
            password: conexionSeleccionada.password, database: dbName, table: tableName
        };

        const response = await apiCall('getTableColumns', 'POST', payload);
        
        if (response.status === 'success') {
            modalBody.innerHTML = renderDynamicForm(response.data, dbName, tableName);
            
            // Asignar evento al formulario dinámico recién creado
            document.getElementById('form-crud-insert').addEventListener('submit', async (e) => {
                e.preventDefault();
                const recordData = Object.fromEntries(new FormData(e.target).entries());
                // Limpiar campos ocultos para enviar solo la data de columnas a PHP
                delete recordData.target_db; delete recordData.target_table;

                const insertPayload = { ...payload, recordData: recordData };
                const insertResp = await apiCall('insertRecord', 'POST', insertPayload);
                
                if (insertResp.status === 'success') {
                    alert('Registro completado. ID: ' + insertResp.inserted_id);
                    window.cerrarModalCrud();
                } else {
                    alert('Error SQL: ' + insertResp.message);
                }
            });
        } else {
            modalBody.innerHTML = `<p class="text-red-500">${response.message}</p>`;
        }
    };

    // INIT
    workspace.innerHTML = views.dashboard(conexionesActivas);
});