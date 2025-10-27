<?php
// Archivo: backend/src/helpers/phpmailer/MailHelper.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Carga los 3 archivos de la librería (que están en esta misma carpeta)
require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';

// Carga nuestra nueva configuración de correo
require_once __DIR__ . '/../../config/mail.php'; 

class MailHelper {

    public static function enviarCorreoDeAlerta($detallesAlerta, $pdo) {
        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();
            $mail->Host       = HOST_SMTP;
            $mail->SMTPAuth   = true;
            $mail->Username   = USUARIO_SMTP;
            $mail->Password   = PASSWORD_SMTP;
            
            // Ajusta esto según tu puerto (465 o 587)
            if (PUERTO_SMTP == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Para SSL
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Para TLS
            }
            $mail->Port       = PUERTO_SMTP;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(REMITENTE_EMAIL, REMITENTE_NOMBRE);

            
            $stmt = $pdo->prepare("SELECT valor FROM alerta_destinatarios WHERE habilitado = 1 AND tipo = 'EMAIL'");
            $stmt->execute();
            $destinatarios = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($destinatarios)) {
                
                error_log("MailHelper: No hay destinatarios habilitados en la BD. Correo no enviado.");
                return false;
            }

            foreach ($destinatarios as $correo) {
                $mail->addAddress(trim($correo));
            }

            // --- 3. Contenido del Correo ---
            $mail->isHTML(true);
            $mail->Subject = "ALERTA DE MONITOREO: {$detallesAlerta['cuarto_nombre']}";
            $mail->Body    = "
                <h1 style='color: #D9534F;'>Alerta de Monitoreo</h1>
                <p>Se ha detectado una lectura fuera de los parámetros:</p>
                <ul>
                    <li><strong>Cuarto:</strong> {$detallesAlerta['cuarto_nombre']}</li>
                    <li><strong>Variable:</strong> {$detallesAlerta['variable']}</li>
                    <li><strong>Valor Medido:</strong> {$detallesAlerta['valor_medido']}</li>
                    <li><strong>Rango Esperado:</strong> {$detallesAlerta['rango_esperado']}</li>
                </ul>
            ";

            $mail->send();
            return true;

        } catch (Exception $e) {
            // Si falla, lo guardamos en el log de errores de cPanel
            error_log("El correo no pudo ser enviado. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}