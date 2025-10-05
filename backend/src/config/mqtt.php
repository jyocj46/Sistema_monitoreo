<?php
// src/config/mqtt.php

require_once __DIR__ . '/db.php'; // Incluye la conexión DB

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// Obtener configuración desde el archivo de entorno
$env = require __DIR__ . '/env.php';

$broker = $env['MQTT_BROKER'] ?: 'mqtt://broker.hivemq.com';
$topic = $env['MQTT_TOPIC'] ?: 'cuartos_frios/lecturas';
$clientId = 'php_' . uniqid();  // Genera un ID único para cada conexión

// Crear el cliente MQTT
$client = new MqttClient($broker, 8883, $clientId);

// Configuración de conexión
$connectionSettings = (new ConnectionSettings)
    ->setUsername($env['MQTT_USER'] ?? null)
    ->setPassword($env['MQTT_PASS'] ?? null)
    ->setKeepAliveInterval(60);

// Conexión al broker MQTT
try {
    $client->connect($connectionSettings);
    echo "✅ Conectado a MQTT: $broker\n";
    $client->subscribe($topic, function ($topic, $message) {
        // Manejar el mensaje recibido
        handleMqttMessage($message);
    });
} catch (Exception $e) {
    echo "❌ Error conectando a MQTT: " . $e->getMessage();
}

// Función para manejar los mensajes recibidos
function handleMqttMessage($message) {
    try {
        echo "Mensaje recibido: $message\n";  // Debug: Imprime el mensaje recibido
        $data = json_decode($message, true);
        if (!$data) {
            echo "❌ Error al decodificar el mensaje MQTT\n";
            return;
        }

        // Extraer los datos del mensaje
        $cuarto_id = $data['cuarto_id'] ?? null;
        $sensor_id = $data['sensor_id'] ?? null;
        $temperatura_c = $data['temperatura_c'] ?? null;
        $humedad_pct = $data['humedad_pct'] ?? null;

        // Validar los datos
        if ($cuarto_id && $sensor_id && $temperatura_c !== null && $humedad_pct !== null) {
            // Verificar estado de la lectura
            $estado = 'OK';
            if ($temperatura_c < -40 || $temperatura_c > 80 || $humedad_pct < 0 || $humedad_pct > 100) {
                $estado = 'SOSPECHOSA';
            }

            // Guardar en base de datos
            saveReadingToDatabase($cuarto_id, $sensor_id, $temperatura_c, $humedad_pct, $estado);
        }
    } catch (Exception $e) {
        echo "❌ Error manejando el mensaje MQTT: " . $e->getMessage();
    }
}


// Función para guardar en la base de datos
function saveReadingToDatabase($cuarto_id, $sensor_id, $temperatura_c, $humedad_pct, $estado) {
    global $pdo;
    $sql = "
        INSERT INTO lectura (cuarto_id, sensor_id, temperatura_c, humedad_pct, origen, estado, tomado_en_utc)
        VALUES (:cuarto_id, :sensor_id, :temperatura_c, :humedad_pct, 'MQTT', :estado, UTC_TIMESTAMP())
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cuarto_id' => $cuarto_id,
        ':sensor_id' => $sensor_id,
        ':temperatura_c' => $temperatura_c,
        ':humedad_pct' => $humedad_pct,
        ':estado' => $estado
    ]);

    echo "✅ Lectura guardada en base de datos.\n";
}

// Mantener la conexión MQTT abierta para recibir mensajes
while ($client->isConnected()) {
    $client->loop();
}

?>
