<?php
date_default_timezone_set('America/Guatemala');
// src/config/db.php

$env = require __DIR__ . '/env.php';

try {
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_NAME']};charset={$env['DB_CHARSET']}",
        $env['DB_USER'],
        $env['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    //echo "✅ Conectado a la base de datos\n";
    $pdo->exec("SET time_zone = '-06:00';");
} catch (PDOException $e) {
    echo "❌ Error al conectar a la base de datos: " . $e->getMessage();
    exit;
}

?>
