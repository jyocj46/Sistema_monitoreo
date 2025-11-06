<?php
// src/models/PushOneSignal.php

class PushOneSignal {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Guarda un nuevo player_id (sin duplicar)
     */
    public function guardarPlayerId(string $playerId): bool {
        try {
            $sql = "INSERT IGNORE INTO push_onesignal (player_id) VALUES (:pid)";
            $stmt = $this->pdo->prepare($sql);  
            return $stmt->execute([':pid' => $playerId]);
        } catch (Exception $e) {
            error_log("Error al guardar player_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los player_id registrados
     */
    public function obtenerTodos(): array {
        $stmt = $this->pdo->query("SELECT player_id FROM push_onesignal");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Elimina un player_id (por ejemplo si el usuario desactiva notificaciones)
     */
    public function eliminarPlayerId(string $playerId): bool {
        try {
            $sql = "DELETE FROM push_onesignal WHERE player_id = :pid";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':pid' => $playerId]);
        } catch (Exception $e) {
            error_log("Error al eliminar player_id: " . $e->getMessage());
            return false;
        }
    }
}
?>
