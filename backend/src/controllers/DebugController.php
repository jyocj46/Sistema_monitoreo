<?php
// Archivo: backend/src/controllers/DebugController.php

class DebugController {
    public function __construct() {}

    public function showLastMailError() {
        
        $logFile = __DIR__ . '/../../last_mail.log';

        header('Content-Type: text/plain; charset=utf-8');

        if (file_exists($logFile)) {
            echo "--- MOSTRANDO LOG DE CORREO (last_mail.log) ---\n";
            echo "Última actualización: " . date("F d Y H:i:s.", filemtime($logFile)) . "\n\n";
            
            readfile($logFile);
        } else {
            echo "El archivo de log ('last_mail.log') no se ha creado todavía.\n";
            echo "Fuerza una alerta de correo para generarlo.";
        }
        exit;
    }
}