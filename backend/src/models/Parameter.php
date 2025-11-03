
<?php
// src/models/Parameter.php 
class Parameter {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todos los parámetros por cuarto con el nuevo esquema:
     * - Temperatura: warn/crit (min/max)
     * - Humedad:     warn/crit (min/max)
     * - Histéresis y habilitado
     */
    public function getTodosLosParametros(): array {
        $sql = "SELECT 
                    c.id   AS cuarto_id,
                    c.nombre AS cuarto_nombre,
                    c.codigo AS cuarto_codigo,
                    p.temp_warn_min_c,
                    p.temp_warn_max_c,
                    p.temp_crit_min_c,
                    p.temp_crit_max_c,
                    p.hum_warn_min_pct,
                    p.hum_warn_max_pct,
                    p.hum_crit_min_pct,
                    p.hum_crit_max_pct,
                    p.`histéresis_c`,
                    p.habilitado
                FROM cuarto c
                LEFT JOIN parametro_cuarto p ON c.id = p.cuarto_id
                ORDER BY c.id ASC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inserta/actualiza parámetros de un cuarto (upsert por UNIQUE(cuarto_id)).
     * Solo cambia esta tabla conforme al nuevo esquema.
     */
    public function actualizarParametro(int $cuartoId, array $data): bool {
        // Acepta 'histeresis_c' (sin acento) o 'histéresis_c' (con acento) en $data
        $histeresis = $data['histeresis_c'] 
                      ?? $data['histéresis_c'] 
                      ?? null; // si es null, usará el DEFAULT (0.50) en inserts

        $sql = "INSERT INTO parametro_cuarto (
                    cuarto_id,
                    temp_warn_min_c,
                    temp_warn_max_c,
                    temp_crit_min_c,
                    temp_crit_max_c,
                    hum_warn_min_pct,
                    hum_warn_max_pct,
                    hum_crit_min_pct,
                    hum_crit_max_pct,
                    `histéresis_c`,
                    habilitado
                ) VALUES (
                    :cuarto_id,
                    :temp_warn_min_c,
                    :temp_warn_max_c,
                    :temp_crit_min_c,
                    :temp_crit_max_c,
                    :hum_warn_min_pct,
                    :hum_warn_max_pct,
                    :hum_crit_min_pct,
                    :hum_crit_max_pct,
                    :histeresis_c,
                    :habilitado
                )
                ON DUPLICATE KEY UPDATE
                    temp_warn_min_c = VALUES(temp_warn_min_c),
                    temp_warn_max_c = VALUES(temp_warn_max_c),
                    temp_crit_min_c = VALUES(temp_crit_min_c),
                    temp_crit_max_c = VALUES(temp_crit_max_c),
                    hum_warn_min_pct = VALUES(hum_warn_min_pct),
                    hum_warn_max_pct = VALUES(hum_warn_max_pct),
                    hum_crit_min_pct = VALUES(hum_crit_min_pct),
                    hum_crit_max_pct = VALUES(hum_crit_max_pct),
                    `histéresis_c`  = VALUES(`histéresis_c`),
                    habilitado      = VALUES(habilitado)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':cuarto_id'         => $cuartoId,
            ':temp_warn_min_c'   => $data['temp_warn_min_c']   ?? null,
            ':temp_warn_max_c'   => $data['temp_warn_max_c']   ?? null,
            ':temp_crit_min_c'   => $data['temp_crit_min_c']   ?? null,
            ':temp_crit_max_c'   => $data['temp_crit_max_c']   ?? null,
            ':hum_warn_min_pct'  => $data['hum_warn_min_pct']  ?? null,
            ':hum_warn_max_pct'  => $data['hum_warn_max_pct']  ?? null,
            ':hum_crit_min_pct'  => $data['hum_crit_min_pct']  ?? null,
            ':hum_crit_max_pct'  => $data['hum_crit_max_pct']  ?? null,
            ':histeresis_c'      => $histeresis,
            ':habilitado'        => $data['habilitado'] ?? 1, 
        ]);
    }
}
