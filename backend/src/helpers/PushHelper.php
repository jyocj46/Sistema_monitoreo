<?php
// /src/helpers/PushHelper.php


class PushHelper {

    public static function enviarNotificacionPush(array $detalles, PDO $pdo): bool {
        try {
            // Cargar config
            $configPath = __DIR__ . '/../config/env.php';
            if (!is_file($configPath)) {
                error_log('PushHelper: no se encontró env.php');
                return true; // no romper el flujo de alertas
            }
            $env = require $configPath;

            $appId = $env['ONE_SIGNAL_APP_ID'] ?? null;
            $apiKey = $env['ONE_SIGNAL_API_KEY'] ?? null;

            if (!$appId || !$apiKey) {
                error_log('PushHelper: faltan ONE_SIGNAL_APP_ID/ONE_SIGNAL_API_KEY en env.php');
                return true;
            }

            // 1) Obtener player_ids
            $stmt = $pdo->query("SELECT player_id FROM push_onesignal");
            $player_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (empty($player_ids)) {
                return true; // sin destinatarios
            }

            // 2) Mensaje
            $titulo = "⚠️ Alerta en {$detalles['cuarto_nombre']}";
            $cuerpo = "{$detalles['variable']} midió {$detalles['valor_medido']}. (Rango: {$detalles['rango_esperado']})";

            // 3) Payload OneSignal
            $fields = [
                'app_id' => $appId,
                'include_player_ids' => $player_ids,
                'headings' => ['en' => $titulo],
                'contents' => ['en' => $cuerpo],
                'web_url' => 'https://drover.detpon.com/frontend/', // opcional: apunta al front
            ];
            $fields_json = json_encode($fields, JSON_UNESCAPED_UNICODE);

            // 4) cURL
            $ch = curl_init('https://onesignal.com/api/v1/notifications');
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json; charset=utf-8',
                    'Authorization: Basic ' . $apiKey,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $fields_json,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            if ($response === false) {
                $err = curl_error($ch);
                error_log("OneSignal cURL error: $err");
                curl_close($ch);
                return true;
            }
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $data = json_decode($response, true);
            if ($httpCode < 200 || $httpCode >= 300 || (isset($data['errors']) && $data['errors'])) {
                error_log("OneSignal HTTP $httpCode: $response");
            }

            return true;

        } catch (Throwable $e) {
            error_log("PushHelper EX: " . $e->getMessage());
            return true; // no romper otras notificaciones
        }
    }
}
