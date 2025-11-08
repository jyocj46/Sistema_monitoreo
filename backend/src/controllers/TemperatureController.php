<?php
// src/controllers/TemperatureController.php

require_once __DIR__ . '/../models/Temperature.php';

class TemperatureController {
    private $temperatureModel;
    private $pdo;

    private $ai_service_url = 'https://ia-cuartos-frios-prod.onrender.com/detectar';
    private $time_steps_ia = 6;
    
    public function __construct($pdo) {
        $this->temperatureModel = new Temperature($pdo);
        $this->pdo = $pdo;
    }

    
    public function getLecturas(
        $sensorId = null,
        $cuartoId = null,
        $limit = 200,
        $fechaInicio = null,
        $fechaFin = null,
        $horaInicio = null,
        $horaFin = null,
        $sortOrder = 'DESC'
        ) {
        try {
            $lecturas = $this->temperatureModel->getLecturas(
                $sensorId,
                $cuartoId,
                $limit,
                $fechaInicio,
                $fechaFin,
                $horaInicio,
                $horaFin,
                $sortOrder
            );

            return [
                'success' => true,
                'data'    => $lecturas,
                'total'   => count($lecturas),
                'filters' => [
                    'sensor_id'    => $sensorId,
                    'cuarto_id'    => $cuartoId,
                    'limit'        => $limit,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin'    => $fechaFin,
                    'hora_inicio'  => $horaInicio,
                    'hora_fin'     => $horaFin,
                    'sort_order'   => $sortOrder
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => 'Error al obtener lecturas',
                'message' => $e->getMessage()
            ];
        }
    }

    public function getCuartos() {
        try {
            $cuartos = $this->temperatureModel->getCuartos();
            return [
                'success' => true,
                'data' => $cuartos
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener cuartos',
                'message' => $e->getMessage()
            ];
        }
    }

    public function getUltimas($by = 'cuarto') {
        try {
            // Validar parámetro 'by'
            $by = in_array($by, ['cuarto', 'sensor']) ? $by : 'cuarto';
            
            $ultimas = $this->temperatureModel->getUltimas($by);
            
            return [
                'success' => true,
                'data' => $ultimas,
                'total' => count($ultimas),
                'group_by' => $by
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener últimas lecturas',
                'message' => $e->getMessage()
            ];
        }
    }
    public function insertarLectura($datos) {
        try {
            // Validar datos requeridos
            $required = ['cuarto_id', 'sensor_id', 'temperatura_c', 'humedad_pct'];
            foreach ($required as $field) {
                if (!isset($datos[$field])) {
                    return [
                        'success' => false,
                        'error' => "Campo requerido: $field"
                    ];
                }
            }

            // Validar tipos de datos
            if (!is_numeric($datos['cuarto_id']) || !is_numeric($datos['sensor_id'])) {
                return [
                    'success' => false,
                    'error' => 'cuarto_id y sensor_id deben ser numéricos'
                ];
            }

            if (!is_numeric($datos['temperatura_c']) || !is_numeric($datos['humedad_pct'])) {
                return [
                    'success' => false,
                    'error' => 'temperatura_c y humedad_pct deben ser numéricos'
                ];
            }

        $success = $this->temperatureModel->insertarLectura(
            (int)$datos['cuarto_id'],
            (int)$datos['sensor_id'],
            (float)$datos['temperatura_c'],
            (float)$datos['humedad_pct'],
            $datos['origen'] ?? 'HTTP',
            $datos['tomado_en_utc'] ?? null 
        );

            if ($success) {
                // Verificar alertas
                $alertas = $this->temperatureModel->verificarAlertas(
                    (int)$datos['cuarto_id'],
                    (int)$datos['sensor_id'],
                    (float)$datos['temperatura_c'],
                    (float)$datos['humedad_pct']
                );

            $ia_check_result = null; 
                try {
                    
                   
                    $ultimas_lecturas = $this->getUltimasLecturasParaIA((int)$datos['cuarto_id']);

                    
                    if (count($ultimas_lecturas) == $this->time_steps_ia) {
                        
                       
                        $ia_result = $this->llamarServicioIA((int)$datos['cuarto_id'], $ultimas_lecturas);
                        $ia_check_result = $ia_result;

                        
                        if ($ia_result && $ia_result['anomalia']) {
                            
                           
                            global $alertController; 
                            
                            if ($alertController && method_exists($alertController, 'crearAlerta')) {
                                $alertController->crearAlerta([
                                    'cuarto_id' => (int)$datos['cuarto_id'],
                                    'sensor_id' => (int)$datos['sensor_id'],
                                    'tipo' => 'IA_ANOMALIA', 
                                    'mensaje' => 'Detectado patrón de comportamiento anómalo por IA. Error: ' . number_format($ia_result['error_reconstruccion'], 4),
                                    'valor_medido' => (float)$datos['temperatura_c'],
                                    'contexto_json' => json_encode($ia_result) 
                                ]);
                            } else {
        
                                error_log("Alerta de IA detectada pero AlertController o metodo crearAlerta no está disponible.");
                            }
                        }
                    }

                } catch (Exception $e) {
                    error_log("Error llamando al servicio de IA: " . $e->getMessage());
                    $ia_check_result = ['error' => $e->getMessage()];
                }
                
                return [
                    'success' => true,
                    'message' => 'Lectura registrada correctamente',
                    'alertas_reglas' => $alertas, 
                    'ia_check' => $ia_check_result 
                ];

            } else {
                return [
                    'success' => false,
                    'error' => 'Error al insertar la lectura en la base de datos'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al insertar lectura',
                'message' => $e->getMessage()
            ];
        }
    }

    public function getEstadisticas($cuartoId, $periodo = 'DAY') {
        try {
            if (!$cuartoId || !is_numeric($cuartoId)) {
                return [
                    'success' => false,
                    'error' => 'cuarto_id requerido y debe ser numérico'
                ];
            }

            $allowedPeriods = ['HOUR', 'DAY', 'WEEK', 'MONTH'];
            if (!in_array(strtoupper($periodo), $allowedPeriods)) {
                $periodo = 'DAY';
            }

            $estadisticas = $this->temperatureModel->getEstadisticasCuarto((int)$cuartoId, $periodo);
            
            return [
                'success' => true,
                'data' => $estadisticas,
                'cuarto_id' => (int)$cuartoId,
                'periodo' => $periodo
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener estadísticas',
                'message' => $e->getMessage()
            ];
        }
    }

    public function getAllTemperatures() {
        return $this->getLecturas();
    }

    public function getPromedios() {
        $fechaInicio = $_GET['fecha_inicio'] ?? null;
        $fechaFin = $_GET['fecha_fin'] ?? null;
        $cuartoId = isset($_GET['cuarto_id']) ? (int)$_GET['cuarto_id'] : null;
        $horaInicio = $_GET['hora_inicio'] ?? null;
        $horaFin = $_GET['hora_fin'] ?? null;

        if (!$fechaInicio || !$fechaFin) {
            http_response_code(400);
            return ['success' => false, 'error' => 'Los parámetros fecha_inicio y fecha_fin son requeridos'];
        }

        try {
            
            $promedios = $this->temperatureModel->getPromediosDiarios(
                $fechaInicio, 
                $fechaFin, 
                $cuartoId, 
                $horaInicio, 
                $horaFin
            );
            return ['success' => true, 'data' => $promedios];
        } catch (Exception $e) {
            http_response_code(500);
            return ['success' => false, 'error' => 'Error al obtener promedios', 'message' => $e->getMessage()];
        }
    }

    public function getLecturasParaGrafica() {
        try {
            $lecturas = $this->temperatureModel->getLecturasParaGrafica();
            // Agrupamos los resultados por cuarto_id para el frontend
            $agrupado = [];
            foreach ($lecturas as $lectura) {
                $agrupado[$lectura['cuarto_id']][] = $lectura;
            }
            return ['success' => true, 'data' => $agrupado];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error al obtener datos para gráfica', 'message' => $e->getMessage()];
        }
    }

    private function llamarServicioIA($cuarto_id, $lecturas) {
        $payload = json_encode([
            'cuarto_id' => $cuarto_id,
            'lecturas' => $lecturas // $lecturas ya es un array de [ {'temp..'}, {'temp..'} ]
        ]);

        $ch = curl_init($this->ai_service_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);

        curl_setopt($ch, CURLOPT_TIMEOUT, 5); 

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
             throw new Exception("Error de cURL llamando a IA: " . $error);
        }

        if ($http_code != 200) {
            throw new Exception("El servicio de IA falló. Código: $http_code. Respuesta: $response");
        }

        return json_decode($response, true); // Devuelve el array asociativo
    }
}

