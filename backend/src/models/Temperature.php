<?php
// /src/models/Temperature.php
class Temperature {
    private $pdo;
    
    public function __construct($pdo) { 
        $this->pdo = $pdo; 
        // Recomendado para LIMIT dinámico:
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    public function getLecturas(?int $sensorId, ?int $cuartoId, int $limit = 200, ?string $fechaInicio = null, ?string $fechaFin = null, ?string $sortOrder = 'DESC'): array {
        $limit = max(1, (int)$limit);

        // Consulta base (sin cambios)
        $sql = "SELECT 
                    m.id, m.cuarto_id, m.sensor_id, m.temperatura_c, m.humedad_pct,
                    m.estado, m.tomado_en_utc, m.ingresado_en,
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
            $sql .= " AND DATE(m.tomado_en_utc) >= :fecha_inicio";
            $params[':fecha_inicio'] = $fechaInicio;
        }
        if ($fechaFin !== null) {
            $sql .= " AND DATE(m.tomado_en_utc) <= :fecha_fin";
            $params[':fecha_fin'] = $fechaFin;
        }

        $order = 'DESC';
            if (strtoupper($sortOrder) === 'ASC') {
                $order = 'ASC';
            }
        
        $sql .= " ORDER BY m.tomado_en_utc $order";

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
            $sql = "SELECT m.*, 
                           r.name AS room_name, r.code AS room_code,
                           s.code AS sensor_code, s.position_note AS sensor_position
                    FROM measurements_raw m
                    INNER JOIN (
                        SELECT sensor_id, MAX(recorded_at_utc) AS max_ts
                        FROM measurements_raw
                        GROUP BY sensor_id
                    ) u ON m.sensor_id = u.sensor_id AND m.recorded_at_utc = u.max_ts
                    INNER JOIN room r   ON m.room_id   = r.id
                    INNER JOIN sensor s ON m.sensor_id = s.id
                    WHERE s.is_active = 1
                    ORDER BY m.sensor_id ASC";
        } else {
            $sql = "SELECT m.*, 
                           r.name AS room_name, r.code AS room_code,
                           s.code AS sensor_code, s.position_note AS sensor_position
                    FROM measurements_raw m
                    INNER JOIN (
                        SELECT room_id, MAX(recorded_at_utc) AS max_ts
                        FROM measurements_raw
                        GROUP BY room_id
                    ) u ON m.room_id = u.room_id AND m.recorded_at_utc = u.max_ts
                    INNER JOIN room r   ON m.room_id   = r.id
                    INNER JOIN sensor s ON m.sensor_id = s.id
                    WHERE s.is_active = 1
                    ORDER BY m.room_id ASC";
        }

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function insertarLectura(
        int $cuartoId,
        int $sensorId,
        ?float $temperatura,
        ?float $humedad,
        string $origen = 'HTTP',
        ?string $tomadoEnUtc = null
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

            return $stmt->execute();
    }

    public function getEstadisticasCuarto(int $roomId, string $periodo = 'DAY'): array {
        $periodo = strtoupper($periodo);
        $allowed = ['HOUR','DAY','WEEK','MONTH'];
        if (!in_array($periodo, $allowed)) $periodo = 'DAY';

        // Agrega por día (como hacía tu versión anterior) sobre recorded_at_utc
        $sql = "SELECT 
                    COUNT(*)              AS total_lecturas,
                    AVG(temperature_c)    AS temp_promedio,
                    MIN(temperature_c)    AS temp_minima,
                    MAX(temperature_c)    AS temp_maxima,
                    AVG(humidity_pct)     AS hum_promedio,
                    MIN(humidity_pct)     AS hum_minima,
                    MAX(humidity_pct)     AS hum_maxima,
                    DATE(recorded_at_utc) AS fecha
                FROM measurements_raw
                WHERE room_id = :room_id
                  AND recorded_at_utc >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 $periodo)
                GROUP BY DATE(recorded_at_utc)
                ORDER BY fecha DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':room_id' => $roomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function verificarAlertas(int $roomId, int $sensorId, float $temperature, float $humidity): array {
        $sql = "SELECT temp_min_c, temp_max_c, hum_min_pct, hum_max_pct, hysteresis_c
                FROM alert_rules
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

    public function getPromediosDiarios(?string $fechaInicio, ?string $fechaFin, ?int $cuartoId) {
        
        $sql = "SELECT
                    DATE(m.tomado_en_utc) AS fecha,
                    m.cuarto_id,
                    r.nombre AS cuarto_nombre,
                    AVG(m.temperatura_c) AS temp_promedio
                FROM lectura m
                INNER JOIN cuarto r ON m.cuarto_id = r.id
                WHERE 1=1";
        
        $params = [];

        if ($fechaInicio !== null) {
            $sql .= " AND DATE(m.tomado_en_utc) >= :fecha_inicio";
            $params[':fecha_inicio'] = $fechaInicio;
        }
        if ($fechaFin !== null) {
            $sql .= " AND DATE(m.tomado_en_utc) <= :fecha_fin";
            $params[':fecha_fin'] = $fechaFin;
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
}
