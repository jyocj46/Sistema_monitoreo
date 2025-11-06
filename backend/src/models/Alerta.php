<?php
// /src/models/Alerta.php

require_once __DIR__ . '/../helpers/phpmailer/MailHelper.php';
require_once __DIR__ . '/../helpers/phpmailer/WhatsappHelper.php';
require_once __DIR__ . '/../helpers/PushHelper.php';

class Alerta {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function verificarYGestionarAlertas(array $lectura) {

        $params = $this->getParametrosPorCuarto($lectura['cuarto_id']);
        if (!$params || !$params['habilitado']) {
            return; 
        }

        
        $nivelTemp = $this->evaluarNivelAlerta(
            $lectura['temperatura_c'],
            $params['temp_warn_min_c'], $params['temp_warn_max_c'],
            $params['temp_crit_min_c'], $params['temp_crit_max_c']
        );
        
        $this->gestionarEstadoAlerta($lectura, $params, 'TEMPERATURA', $nivelTemp);

        
        $nivelHum = $this->evaluarNivelAlerta(
            $lectura['humedad_pct'],
            $params['hum_warn_min_pct'], $params['hum_warn_max_pct'],
            $params['hum_crit_min_pct'], $params['hum_crit_max_pct']
        );
        
        $this->gestionarEstadoAlerta($lectura, $params, 'HUMEDAD', $nivelHum);
    }

    private function gestionarEstadoAlerta($lectura, $params, $variable, $nuevoNivel) {
        $alertaAbierta = $this->getAlertaAbierta($lectura['cuarto_id'], $variable);
        $prioridadAbierta = $alertaAbierta ? $alertaAbierta['prioridad'] : null;

        if ($nuevoNivel === 'OK') {

            if ($alertaAbierta) {
                $this->cerrarAlerta($alertaAbierta['id']);
            }
        } else {

            $valorMedido = ($variable === 'TEMPERATURA') ? $lectura['temperatura_c'] : $lectura['humedad_pct'];
            $rangoWarn = ($variable === 'TEMPERATURA') ? "{$params['temp_warn_min_c']} - {$params['temp_warn_max_c']}" : "{$params['hum_warn_min_pct']} - {$params['hum_warn_max_pct']}";
            $rangoCrit = ($variable === 'TEMPERATURA') ? "{$params['temp_crit_min_c']} - {$params['temp_crit_max_c']}" : "{$params['hum_crit_min_pct']} - {$params['hum_crit_max_pct']}";

            if (!$alertaAbierta) {
    
                $this->crearAlerta($lectura, $variable, $valorMedido, $nuevoNivel, $rangoWarn, $rangoCrit, $params['cuarto_nombre']);

            } else if ($nuevoNivel !== $prioridadAbierta) {
    
                $this->actualizarPrioridadAlerta($alertaAbierta['id'], $nuevoNivel);

                if ($nuevoNivel === 'ALTA') {
                    $this->notificar(
                        $nuevoNivel, 
                        $this->crearDetallesNotificacion($params['cuarto_nombre'], $variable, $valorMedido, $rangoWarn, $rangoCrit)
                    );
                }
            }
        }
    }

    private function evaluarNivelAlerta($valorMedido, $warn_min, $warn_max, $crit_min, $crit_max) {
        
        if ($crit_min !== null && $valorMedido < $crit_min) return 'ALTA';
        if ($crit_max !== null && $valorMedido > $crit_max) return 'ALTA';

        if ($warn_min !== null && $valorMedido < $warn_min) return 'MEDIA';
        if ($warn_max !== null && $valorMedido > $warn_max) return 'MEDIA';
        
        return 'OK';
    }

    private function getParametrosPorCuarto(int $cuartoId) {
        $stmt = $this->pdo->prepare("SELECT pc.*, c.nombre AS cuarto_nombre FROM parametro_cuarto pc JOIN cuarto c ON pc.cuarto_id = c.id WHERE pc.cuarto_id = :cuarto_id");
        $stmt->execute([':cuarto_id' => $cuartoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getAlertaAbierta(int $cuartoId, string $variable) {
        $stmt = $this->pdo->prepare("SELECT * FROM alerta WHERE cuarto_id = :cuarto_id AND variable = :variable AND estado = 'ABIERTA'");
        $stmt->execute([':cuarto_id' => $cuartoId, ':variable' => $variable]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function crearAlerta($lectura, $variable, $valorMedido, $prioridad, $rangoWarn, $rangoCrit, $cuartoNombre) {
    
        $sql = "INSERT INTO alerta (
                    cuarto_id,
                    sensor_id,
                    prioridad,
                    variable,
                    valor_medido,
                    umbral_min,
                    umbral_max,
                    estado,
                    abierta_en_utc
                ) VALUES (
                    :cuarto_id,
                    :sensor_id,
                    :prioridad,
                    :variable,
                    :valor_medido,
                    :umbral_min,
                    :umbral_max,
                    'ABIERTA',
                    UTC_TIMESTAMP()
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':cuarto_id'    => $lectura['cuarto_id'],
            ':sensor_id'    => $lectura['sensor_id'],
            ':prioridad'    => $prioridad,   
            ':variable'     => $variable,
            ':valor_medido' => $valorMedido,
            ':umbral_min'   => $rangoWarn,   
            ':umbral_max'   => $rangoCrit    
        ]);

        $detalles = $this->crearDetallesNotificacion($cuartoNombre, $variable, $valorMedido, $rangoWarn, $rangoCrit);
        $this->notificar($prioridad, $detalles);
    }

    private function crearDetallesNotificacion($cuartoNombre, $variable, $valorMedido, $rangoWarn, $rangoCrit) {
        $rango = ($variable === 'TEMPERATURA') ? 'Rango Advertencia' : 'Rango Advertencia';
        return [
            'cuarto_nombre' => $cuartoNombre,
            'variable'      => $variable,
            'valor_medido'  => $valorMedido,
            'rango_esperado' => $rangoWarn // Enviamos el rango de advertencia en la notificación
        ];
    }

    private function notificar($prioridad, $detalles) {
        try {
            $esMediaAlta = ($prioridad === 'MEDIA' || $prioridad === 'ALTA');
            $esAlta = ($prioridad === 'ALTA');            
            if ($esMediaAlta) {    
                MailHelper::enviarCorreoDeAlerta($detalles, $this->pdo);                
                try {
                    PushHelper::enviarNotificacionPush($detalles, $this->pdo);
                } catch (Exception $e) {
                    error_log("Fallo al enviar Push: " . $e->getMessage());        
                }
            }            
            if ($esAlta) {    
                WhatsAppHelper::enviarMensajeAlerta($detalles, $this->pdo);
            }            
        } catch (Exception $e) {
            error_log("Fallo general en notificar: " . $e->getMessage());
        }
    }

    private function actualizarPrioridadAlerta($alertaId, $nuevaPrioridad) {
        $sql = "UPDATE alerta SET prioridad = :prioridad WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':prioridad' => $nuevaPrioridad, ':id' => $alertaId]);
    }

    private function cerrarAlerta(int $alertaId) {
        $sql = "UPDATE alerta SET estado = 'CERRADA', cerrada_en_utc = UTC_TIMESTAMP() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $alertaId]);
    }
    
    public function getAlertasActivas(): array {
        $sql = "SELECT a.*, c.nombre AS cuarto_nombre 
                FROM alerta a 
                JOIN cuarto c ON a.cuarto_id = c.id
                WHERE a.estado = 'ABIERTA' 
                ORDER BY a.abierta_en_utc DESC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}