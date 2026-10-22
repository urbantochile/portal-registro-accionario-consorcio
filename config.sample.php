<?php
// Copia de referencia. install.php genera config.php automáticamente.
return [
    'db' => ['host' => 'localhost', 'port' => 3306, 'name' => 'NOMBRE_BASE', 'user' => 'USUARIO_BASE', 'pass' => 'CLAVE_BASE'],
    'base_url' => 'https://socialis.consorciosalinero.cl',
    'storage_path' => __DIR__ . '/storage',
    'timezone' => 'America/Santiago',
    'session_idle_minutes' => 30,
    'debug' => false,
];
