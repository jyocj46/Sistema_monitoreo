<?php
// /src/models/Temperature.php

require_once __DIR__ . '/Alerta.php';

class Temperature {
    private $pdo;
    private $alertaModel; 
    
    public function __construct($pdo) { 
        $this->pdo = $pdo; 
        $this->alertaModel = new Alerta($pdo);

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // más seguro
    }

    public function getLecturas(?int $sensorId, ?int $cuartoId, int $limit = 200,
        ?string $fechaInicio = null, ?string $fechaFin = null,
        ?string $horaInicio = null, ?string $horaFin = null, ?string $sortOrder = 'DESC'): array
        {
        $limit = max(1, (int)$limit);

        $sql = "SELECT 
                    m.id, m.cuarto_id, m.sensor_id, m.temperatura_c, m.humedad_pct,
                    m.estado, m.tomado_en_utc, m.ingresado_en,
                    DATE_ADD(m.ingresado_en, INTERVAL 1 HOUR) AS ingresado_local,
                    r.nombre AS cuarto_nombre, r.codigo AS cuarto_codigo,
                    s.codigo AS sensor_codigo, s.ubicacion AS sensor_ubicacion
                FROM lectura m
                INNER JOIN cuarto r ON m.cuarto_id = r.id
                INNER JOIN sensor s ON m.sensor_id = s.id
                WHERE 1=1";

        $params = [];

        if ($sensorId !== null) {
            $sql .= " AND m.sensor_id = :sensor_id";
            $params[':sensor_id'] = $sensorId;
        }
        if ($cuartoId !== null) {
            $sql .= " AND m.cuarto_id = :room_id";
            $params[':room_id'] = $cuartoId;
        }

        if ($fechaInicio !== null) {
            $fechaHoraInicio = $fechaInicio . ' ' . ($horaInicio ?? '00:00:00');
            
            $stmtTmp = $this->pdo->prepare("SELECT DATE_SUB(:fh, INTERVAL 1 HOUR)");
            $stmtTmp->execute([':fh' => $fechaHoraInicio]);
            $ajusteInicio = $stmtTmp->fetchColumn();

            $sql .= " AND m.ingresado_en >= :fecha_inicio";
            $params[':fecha_inicio'] = $ajusteInicio;
        }

        if ($fechaFin !== null) {
            $fechaHoraFin = $fechaFin . ' ' . ($horaFin ?? '23:59:59');

            $stmtTmp = $this->pdo->prepare("SELECT DATE_SUB(:fh, INTERVAL 1 HOUR)");
            $stmtTmp->execute([':fh' => $fechaHoraFin]);
            $ajusteFin = $stmtTmp->fetchColumn();

            $sql .= " AND m.ingresado_en <= :fecha_fin";
            $params[':fecha_fin'] = $ajusteFin;
        }

        $order = (strtoupper($sortOrder) === 'ASC') ? 'ASC' : 'DESC';
        $sql .= " ORDER BY m.ingresado_en $order, m.id $order";

        if ($fechaInicio === null && $fechaFin === null) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = $limit;
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => &$val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCuartos(): array {
        $sql = "SELECT id, nombre, codigo FROM cuarto ORDER BY nombre ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUltimas(string $by = 'cuarto'): array {
        if ($by === 'sensor') {
            $sql = "
                SELECT id, cuarto_id, sensor_id, temperatura_c, humedad_pct, estado,
                    tomado_en_utc, ingresado_en,
                    r.nombre AS cuarto_nombre, r.codigo AS cuarto_codigo,
                    s.codigo AS sensor_codigo, s.ubicacion AS sensor_ubicacion
                FROM (
                    SELECT m.*,
                        ROW_NUMBER() OVER (PARTITION BY m.sensor_id ORDER BY m.tomado_en_utc DESC) AS rn
                    FROM lectura m
                ) x
                INNER JOIN cuarto r ON x.cuarto_id = r.id
                INNER JOIN sensor s ON x.sensor_id = s.id
                WHERE rn = 1 AND s.activo = 1
                ORDER BY x.sensor_id ASC";
        } else {
            $sql = "
                SELECT id, cuarto_id, sensor_id, temperatura_c, humedad_pct, estado,
                    tomado_en_utc, ingresado_en,
                    r.nombre AS cuarto_nombre, r.codigo AS cuarto_codigo,
                    s.codigo AS sensor_codigo, s.ubicacion AS sensor_ubicacion
                FROM (
                    SELECT m.*,
                        ROW_NUMBER() OVER (PARTITION BY m.cuarto_id ORDER BY m.tomado_en_utc DESC) AS rn
                    FROM lectura m
                ) x
                INNER JOIN cuarto r ON x.cuarto_id = r.id
                INNER JOIN sensor s ON x.sensor_id = s.id
                WHERE rn = 1 AND s.activo = 1
                ORDER BY x.cuarto_id ASC";
        }

        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $cuartoIds = [];
        foreach ($rows as $r) {
            if (isset($r['cuarto_id'])) {
                $cuartoIds[(int)$r['cuarto_id']] = true;
            }
        }
        $cuartoIds = array_keys($cuartoIds);
        if (empty($cuartoIds)) {
            return $rows;
        }

        $placeholders = implode(',', array_fill(0, count($cuartoIds), '?'));
        $sqlIa = "
            SELECT DISTINCT cuarto_id
            FROM alerta
            WHERE notas LIKE 'IA_ANOMALIA::%'  
            AND estado = 'ABIERTA'             
            AND cuarto_id IN ($placeholders)
        ";

        $iaPorCuarto = [];
        try {
            $stmtIa = $this->pdo->prepare($sqlIa);
            $stmtIa->execute($cuartoIds);
            foreach ($stmtIa->fetchAll(PDO::FETCH_COLUMN, 0) as $cid) {
                $iaPorCuarto[(int)$cid] = true;
            }
        } catch (\Throwable $e) {  
            error_log("getUltimas: no se pudo evaluar IA_ANOMALIA: " . $e->getMessage());
            return $rows;
        }

        if (empty($iaPorCuarto)) {
            return $rows;
        }
        foreach ($rows as &$r) {
            $cid = isset($r['cuarto_id']) ? (int)$r['cuarto_id'] : null;
            if ($cid !== null && isset($iaPorCuarto[$cid])) {
                $r['prioridad'] = 'IA'; 
            }
        }
        unset($r);

        return $rows;
    }

    
    public function insertarLectura(int $cuartoId,int $sensorId,?float $temperatura,?float $humedad,string $origen = 'HTTP',?string $tomadoEnUtc = null
        ): bool {
            
            $sql = "INSERT INTO lectura
                    (cuarto_id, sensor_id, temperatura_c, humedad_pct, origen, tomado_en_utc, ingresado_en, estado)
                    VALUES
                    (:cuarto_id, :sensor_id, :temperatura, :humedad, :origen, :tomado_en_utc, :ingresado_en, 'OK')";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':cuarto_id', $cuartoId, \PDO::PARAM_INT);
            $stmt->bindValue(':sensor_id', $sensorId, \PDO::PARAM_INT);
            $stmt->bindValue(':temperatura', $temperatura);
            $stmt->bindValue(':humedad', $humedad);
            $stmt->bindValue(':origen', $origen);
            $tomadoSql = null;
            if (!empty($tomadoEnUtc)) {
                $ts = strtotime($tomadoEnUtc);
                if ($ts !== false) {
                    $tomadoSql = gmdate('Y-m-d H:i:s', $ts);
                }
            }
            
            if ($tomadoSql) {
                $stmt->bindValue(':tomado_en_utc', $tomadoSql);
            } else {
                
                $utc_now = $this->pdo->query("SELECT UTC_TIMESTAMP()")->fetchColumn();
                $stmt->bindValue(':tomado_en_utc', $utc_now);
            }

            $local_now_corrected = $this->pdo->query("SELECT DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn();
            $stmt->bindValue(':ingresado_en', $local_now_corrected);

            $success = $stmt->execute();

        if ($success) {                
                try {
                    $this->alertaModel->verificarYGestionarAlertas([
                        'cuarto_id'     => $cuartoId,
                        'sensor_id'     => $sensorId,
                        'temperatura_c' => $temperatura,
                        'humedad_pct'   => $humedad
                    ]);
                } catch (Exception $e) {
                    error_log("Fallo al verificar alertas: " . $e->getMessage());
                }
            }
            return $success;
    }

    public function getEstadisticasCuarto(int $cuartoId, string $periodo = 'DAY'): array {
        $periodo = strtoupper($periodo);
        $allowed = ['HOUR','DAY','WEEK','MONTH'];
        if (!in_array($periodo, $allowed, true)) $periodo = 'DAY';

        // Ventana móvil de 1 periodo hacia atrás desde ahora (UTC)
        $sql = "SELECT 
                    COUNT(*)                 AS total_lecturas,
                    AVG(temperatura_c)       AS temp_promedio,
                    MIN(temperatura_c)       AS temp_minima,
                    MAX(temperatura_c)       AS temp_maxima,
                    AVG(humedad_pct)         AS hum_promedio,
                    MIN(humedad_pct)         AS hum_minima,
                    MAX(humedad_pct)         AS hum_maxima,
                    DATE(tomado_en_utc)      AS fecha
                FROM lectura
                WHERE cuarto_id = :cuarto_id
                AND tomado_en_utc >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 $periodo)
                GROUP BY DATE(tomado_en_utc)
                ORDER BY fecha DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cuarto_id' => $cuartoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function verificarAlertas(int $roomId, int $sensorId, float $temperature, float $humidity): array {
        $sql = "SELECT temp_min_c, temp_max_c, hum_min_pct, hum_max_pct, hysteresis_c
                FROM parametro_cuarto
                WHERE room_id = :room_id
                  AND (sensor_id = :sensor_id OR sensor_id IS NULL)
                  AND enabled = 1
                ORDER BY sensor_id IS NOT NULL DESC
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':room_id' => $roomId, ':sensor_id' => $sensorId]);
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);

        $alerts = [];
        if ($rule) {
            if ($rule['temp_min_c'] !== null && $temperature < (float)$rule['temp_min_c']) {
                $alerts[] = ['tipo' => 'TEMP_BAJA', 'valor' => $temperature, 'min' => (float)$rule['temp_min_c']];
            }
            if ($rule['temp_max_c'] !== null && $temperature > (float)$rule['temp_max_c']) {
                $alerts[] = ['tipo' => 'TEMP_ALTA', 'valor' => $temperature, 'max' => (float)$rule['temp_max_c']];
            }
            if ($rule['hum_min_pct'] !== null && $humidity < (float)$rule['hum_min_pct']) {
                $alerts[] = ['tipo' => 'HUM_BAJA', 'valor' => $humidity, 'min' => (float)$rule['hum_min_pct']];
            }
            if ($rule['hum_max_pct'] !== null && $humidity > (float)$rule['hum_max_pct']) {
                $alerts[] = ['tipo' => 'HUM_ALTA', 'valor' => $humidity, 'max' => (float)$rule['hum_max_pct']];
            }
        }
        return $alerts;
    }

    public function getPromediosDiarios(?string $fechaInicio, ?string $fechaFin, ?int $cuartoId,
        ?string $horaInicio = null, ?string $horaFin = null) {

        $sql = "SELECT
                    DATE(m.ingresado_en) AS fecha,
                    m.cuarto_id,
                    r.nombre AS cuarto_nombre,
                    AVG(m.temperatura_c) AS temp_promedio
                FROM lectura m
                INNER JOIN cuarto r ON m.cuarto_id = r.id
                WHERE 1=1";
        $params = [];

        if ($fechaInicio !== null) {
            $sql .= " AND m.ingresado_en >= :fecha_inicio";
            $params[':fecha_inicio'] = $fechaInicio . ' ' . ($horaInicio ?? '00:00:00');
        }
        if ($fechaFin !== null) {
            $sql .= " AND m.ingresado_en <= :fecha_fin";
            $params[':fecha_fin'] = $fechaFin . ' ' . ($horaFin ?? '23:59:59');
        }
        if ($cuartoId !== null) {
            $sql .= " AND m.cuarto_id = :cuarto_id";
            $params[':cuarto_id'] = $cuartoId;
        }

        $sql .= " GROUP BY fecha, m.cuarto_id, r.nombre ORDER BY fecha ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLecturasParaGrafica(int $limitPorCuarto = 30): array {
        
            $sql = "
                SELECT id, cuarto_id, cuarto_nombre, temperatura_c, ingresado_en
                FROM (
                    SELECT
                        l.id,
                        l.cuarto_id,
                        c.nombre AS cuarto_nombre,
                        l.temperatura_c,
                        l.ingresado_en,
                        @rn := IF(@prev_cuarto_id = l.cuarto_id, @rn + 1, 1) AS rn,
                        @prev_cuarto_id := l.cuarto_id
                    FROM lectura l
                    INNER JOIN cuarto c ON l.cuarto_id = c.id
                    CROSS JOIN (SELECT @rn := 0, @prev_cuarto_id := NULL) AS vars
                    WHERE l.ingresado_en >= DATE_SUB(NOW(), INTERVAL 1 DAY) -- Filtra el último día según la hora local
                    ORDER BY l.cuarto_id, l.ingresado_en DESC
                ) AS sub
                WHERE rn <= :limit_por_cuarto
                ORDER BY cuarto_id, ingresado_en ASC;
            ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit_por_cuarto', $limitPorCuarto, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUltimasLecturasParaIA($cuarto_id, $limit) {
        $sql = "SELECT temperatura_c, humedad_pct 
                FROM lectura 
                WHERE cuarto_id = ? 
                ORDER BY tomado_en_utc DESC 
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cuarto_id, $limit]);  
        $lecturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_reverse($lecturas);  
    }


}
