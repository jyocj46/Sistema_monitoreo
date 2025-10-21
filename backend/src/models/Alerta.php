<?php
// /src/models/Alerta.php

class Alerta {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Función principal para verificar y gestionar alertas
    public function verificarYGestionarAlertas(array $lectura) {
        // 1. Obtener los parámetros para el cuarto de la lectura
        $params = $this->getParametrosPorCuarto($lectura['cuarto_id']);
        if (!$params || !$params['habilitado']) {
            return; // No hacer nada si no hay parámetros o están deshabilitados
        }

        // 2. Verificar la temperatura
        $this->evaluarVariable(
            $lectura, $params, 'TEMPERATURA', 
            $lectura['temperatura_c'], $params['temp_min_c'], $params['temp_max_c']
        );

        // 3. Verificar la humedad
        $this->evaluarVariable(
            $lectura, $params, 'HUMEDAD',
            $lectura['humedad_pct'], $params['hum_min_pct'], $params['hum_max_pct']
        );
    }

    private function evaluarVariable($lectura, $params, $variable, $valorMedido, $min, $max) {
        $alertaAbierta = $this->getAlertaAbierta($lectura['cuarto_id'], $variable);
        $fueraDeRango = ($valorMedido < $min) || ($valorMedido > $max);

        if ($fueraDeRango && !$alertaAbierta) {
            // Caso 1: Valor fuera de rango y no hay alerta abierta -> CREAR ALERTA
            $this->crearAlerta($lectura, $variable, $valorMedido, $min, $max);
        } elseif (!$fueraDeRango && $alertaAbierta) {
            // Caso 2: Valor dentro de rango y SÍ hay alerta abierta -> CERRAR ALERTA
            $this->cerrarAlerta($alertaAbierta['id']);
        }
    }

    private function getParametrosPorCuarto(int $cuartoId) {
        $stmt = $this->pdo->prepare("SELECT * FROM parametro_cuarto WHERE cuarto_id = :cuarto_id");
        $stmt->execute([':cuarto_id' => $cuartoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getAlertaAbierta(int $cuartoId, string $variable) {
        $stmt = $this->pdo->prepare("SELECT * FROM alerta WHERE cuarto_id = :cuarto_id AND variable = :variable AND estado = 'ABIERTA'");
        $stmt->execute([':cuarto_id' => $cuartoId, ':variable' => $variable]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function crearAlerta($lectura, $variable, $valorMedido, $min, $max) {
        $sql = "INSERT INTO alerta (cuarto_id, sensor_id, prioridad, variable, valor_medido, umbral_min, umbral_max, estado, abierta_en_utc)
                VALUES (:cuarto_id, :sensor_id, :prioridad, :variable, :valor_medido, :umbral_min, :umbral_max, 'ABIERTA', UTC_TIMESTAMP())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':cuarto_id'    => $lectura['cuarto_id'],
            ':sensor_id'    => $lectura['sensor_id'],
            ':prioridad'    => 'ALTA', // Podrías hacerlo más complejo después
            ':variable'     => $variable,
            ':valor_medido' => $valorMedido,
            ':umbral_min'   => $min,
            ':umbral_max'   => $max
        ]);
    }

    private function cerrarAlerta(int $alertaId) {
        $sql = "UPDATE alerta SET estado = 'CERRADA', cerrada_en_utc = UTC_TIMESTAMP() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $alertaId]);
    }
    
    // Función para el frontend: obtener todas las alertas activas
    public function getAlertasActivas(): array {
        $sql = "SELECT a.*, c.nombre AS cuarto_nombre 
                FROM alerta a 
                JOIN cuarto c ON a.cuarto_id = c.id
                WHERE a.estado = 'ABIERTA' 
                ORDER BY a.abierta_en_utc DESC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}