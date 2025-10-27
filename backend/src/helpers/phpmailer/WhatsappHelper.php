<?php
// /src/helpers/WhatsappHelper.php
// ¡VERSIÓN "A PRUEBA DE BALAS" SIN DEPENDENCIAS!


class WhatsAppHelper {

    public static function enviarMensajeAlerta($detallesAlerta, $pdo) {
        
        // --- 1. Credenciales de Twilio (¡Puestas aquí directamente!) ---
        // ¡Asegúrate de que estas 3 líneas sean correctas!
        $twilio_sid   = 'AC1bc9aaff6a24beb310134f045fdc4f15';
        $twilio_token = 'ae7a8c0c7abb5c33d71a1fc1f858843e';
        $twilio_from  = 'whatsapp:+14155238886'; // Tu número de Sandbox
        // -----------------------------------------------------------

        try {
            if (!function_exists('curl_version')) {
                error_log("WA: cURL no disponible en PHP");
                return true;
            }

            $stmt = $pdo->prepare("SELECT valor FROM alerta_destinatarios WHERE habilitado = 1 AND tipo = 'WHATSAPP'");
            $stmt->execute();
            $destinatarios = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($destinatarios)) {
                error_log("WA: No hay destinatarios 'WHATSAPP' en la BD.");
                return true; 
            }

            // --- Mensaje (Plantilla de Sandbox) ---
            $cuarto   = isset($detallesAlerta['cuarto_nombre']) ? $detallesAlerta['cuarto_nombre'] : 'N/D';
            $variable = isset($detallesAlerta['variable']) ? $detallesAlerta['variable'] : 'Temp';
            $valor    = isset($detallesAlerta['valor_medido']) ? $detallesAlerta['valor_medido'] : 'N/D';
            $rango    = isset($detallesAlerta['rango_esperado']) ? $detallesAlerta['rango_esperado'] : 'N/D';
            
            $valor1 = "ALERTA en {$cuarto}";
            $valor2 = "{$variable}: {$valor} (Rango: {$rango})";
            $mensaje = "Your appointment is coming up on {$valor1} at {$valor2}";

            $url = "https://api.twilio.com/2010-04-01/Accounts/" . $twilio_sid . "/Messages.json";

            foreach ($destinatarios as $numero) {
                $to = 'whatsapp:' . ltrim(trim($numero));
                $data = array('To' => $to, 'From' => $twilio_from, 'Body' => $mensaje);

                $ch = curl_init();
                curl_setopt_array($ch, array(
                    CURLOPT_URL            => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => http_build_query($data),
                    CURLOPT_USERPWD        => $twilio_sid . ':' . $twilio_token,
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_CONNECTTIMEOUT => 5
                ));

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode >= 300) {
                    error_log("WA: Error enviando a {$to}. HTTP {$httpCode}. Respuesta: {$response}");
                }
            }
            return true;
        
        } catch (Exception $e) { // Usamos "Exception" (compatible con todo)
            error_log("WA EX: " . $e->getMessage());
            return true; 
        }
    }
}
