<?php
// Archivo: backend/src/config/mail.php

// --- RELLENA ESTOS 4 DATOS DE TU CPANEL ---
define('HOST_SMTP', 'mail.detpon.com');       // El Servidor Saliente que encontraste
define('PUERTO_SMTP', 465);                   // El Puerto (465 o 587)
define('USUARIO_SMTP', 'mcf@detpon.com'); // Tu correo
define('PASSWORD_SMTP', 'sismonitoreo@2025'); 

// --- DATOS DEL REMITENTE ---
define('REMITENTE_EMAIL', 'mcf@detpon.com');
define('REMITENTE_NOMBRE', 'Sistema de Alertas');

// --- ¡IMPORTANTE! Lista de correos que recibirán la alerta ---
// Separa cada correo con una coma (,)
//define('LISTA_DESTINATARIOS', 'yoc915@gmail.com, jyocj@miumg.edu.gt, isaac.150iemo@gmail.com');
?>