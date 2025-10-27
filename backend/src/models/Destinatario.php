<?php
// Archivo: /src/models/Destinatario.php

class Destinatario {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getDestinatarios() {
        $stmt = $this->pdo->query("SELECT * FROM alerta_destinatarios ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addDestinatario($nombre, $tipo, $valor) {
        $sql = "INSERT IGNORE INTO alerta_destinatarios (nombre, tipo, valor, habilitado) VALUES (:nombre, :tipo, :valor, 1)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':valor' => $valor
        ]);
    }

    public function deleteDestinatario($id) {
        $sql = "DELETE FROM alerta_destinatarios WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
?>