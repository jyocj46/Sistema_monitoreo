<?php

class Parameter {
        private $pdo;

        public function __construct($pdo) {
            $this->pdo = $pdo;
        }

        
        public function getTodosLosParametros(): array {
            $sql = "SELECT 
                        c.id AS cuarto_id,
                        c.nombre AS cuarto_nombre,
                        c.codigo AS cuarto_codigo,
                        p.temp_min_c,
                        p.temp_max_c,
                        p.hum_min_pct,
                        p.hum_max_pct,
                        p.habilitado
                    FROM cuarto c
                    LEFT JOIN parametro_cuarto p ON c.id = p.cuarto_id
                    ORDER BY c.id ASC";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }


        // Actualiza o inserta un parámetro para un cuarto específico
        public function actualizarParametro(int $cuartoId, array $data): bool {
            $sql = "INSERT INTO parametro_cuarto (cuarto_id, temp_min_c, temp_max_c, hum_min_pct, hum_max_pct)
                    VALUES (:cuarto_id, :temp_min_c, :temp_max_c, :hum_min_pct, :hum_max_pct)
                    ON DUPLICATE KEY UPDATE
                        temp_min_c = VALUES(temp_min_c),
                        temp_max_c = VALUES(temp_max_c),
                        hum_min_pct = VALUES(hum_min_pct),
                        hum_max_pct = VALUES(hum_max_pct)";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':cuarto_id'   => $cuartoId,
                ':temp_min_c'  => $data['temp_min_c'] ?? null,
                ':temp_max_c'  => $data['temp_max_c'] ?? null,
                ':hum_min_pct' => $data['hum_min_pct'] ?? null,
                ':hum_max_pct' => $data['hum_max_pct'] ?? null,
            ]);
    }
}