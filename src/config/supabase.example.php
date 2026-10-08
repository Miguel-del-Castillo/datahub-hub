<?php

/**
 * PLANTILLA de configuración de conexión a Supabase Auth (GoTrue REST API).
 *
 * Cómo usarla:
 *   1. Copia este archivo como supabase.php (en esta misma carpeta).
 *   2. Sustituye los marcadores por los valores de tu proyecto de Supabase.
 *
 * supabase.php está en .gitignore y no se sube al repositorio.
 * Este archivo (supabase.example.php) sí se sube, así que NUNCA pongas
 * aquí valores reales.
 */

// URL base de tu proyecto Supabase (Project Settings → Data API)
// Formato: https://xxxxxxxxxxxx.supabase.co
define('SUPABASE_URL', 'https://TU-PROYECTO.supabase.co');

// Publishable key (Project Settings → API Keys → Publishable key)
// Formato: sb_publishable_xxxxxxxxxxxxxxxxxxxx
// Nunca pongas aquí la secret key (sb_secret_...).
define('SUPABASE_PUBLISHABLE_KEY', 'TU_PUBLISHABLE_KEY_AQUI');

// URL base de la API de autenticación (GoTrue).
// La construimos aquí para no repetir "/auth/v1" en cada script.
define('SUPABASE_AUTH_URL', SUPABASE_URL . '/auth/v1');
