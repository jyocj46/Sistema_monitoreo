<?php
// /src/helpers/WhatsappHelper.php
require_once __DIR__ . '/../../config/env.php'; // Cambiar por env.php

class WhatsAppHelper {
    public static function enviarMensajeAlerta($detallesAlerta, $pdo) {
        try {
            // Cargar configuración
            $config = require __DIR__ . '/../../config/env.php';
            
            $stmt = $pdo->prepare("SELECT valor FROM alerta_destinatarios WHERE habilitado = 1 AND tipo = 'WHATSAPP'");
            $stmt->execute();
            $destinatarios = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($destinatarios)) {
                error_log("UltraMsg: No hay destinatarios 'WHATSAPP' habilitados en la BD.");
                return true;
            }       
            
            $cuarto   = isset($detallesAlerta['cuarto_nombre']) ? $detallesAlerta['cuarto_nombre'] : 'N/D';
            $variable = isset($detallesAlerta['variable']) ? $detallesAlerta['variable'] : 'Temp';
            $valor    = isset($detallesAlerta['valor_medido']) ? $detallesAlerta['valor_medido'] : 'N/D';
            $rango    = isset($detallesAlerta['rango_esperado']) ? $detallesAlerta['rango_esperado'] : 'N/D';            
            
            $mensaje = "⚠️ *ALERTA DE MONITOREO* ⚠️\n\n";
            $mensaje .= "*Cuarto:* {$cuarto}\n";
            $mensaje .= "*Variable:* {$variable}\n";
            $mensaje .= "*Medición:* *{$valor}*\n"; 
            $mensaje .= "*Rango Esperado:* {$rango}";
            
            $url = "https://api.ultramsg.com/" . $config['ULTRAMSG_ID'] . "/messages/chat"; // Cambio aquí

            foreach ($destinatarios as $numero) {
                $numeroLimpio = ltrim(trim($numero), '+'); 

                $data = array(
                    'token' => $config['ULTRAMSG_TOKEN'], // Cambio aquí
                    'to' => $numeroLimpio,
                    'body' => $mensaje
                );

                $ch = curl_init();
                curl_setopt_array($ch, array(
                    CURLOPT_URL            => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => http_build_query($data), 
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_CONNECTTIMEOUT => 5
                ));

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode >= 300) {
                    error_log("UltraMsg: Error enviando a {$numeroLimpio}. HTTP {$httpCode}. Respuesta: {$response}");
                }
            }
            return true;

        } catch (Exception $e) { 
            error_log("UltraMsg EX: " . $e->getMessage());
            return true; 
        }
    }
}
?>