<?php
// Archivo: /src/models/Destinatario.php

class Destinatario {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Obtener todos los destinatarios
    public function getDestinatarios() {
        $stmt = $this->pdo->query("SELECT * FROM alerta_destinatarios ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Añadir un nuevo destinatario
    public function addDestinatario($email, $nombre) {
        // Usamos IGNORE para evitar errores si el email ya existe (UNIQUE)
        $sql = "INSERT IGNORE INTO alerta_destinatarios (email, nombre, habilitado) VALUES (:email, :nombre, 1)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':email' => $email, ':nombre' => $nombre]);
    }

    // Eliminar un destinatario
    public function deleteDestinatario($id) {
        $sql = "DELETE FROM alerta_destinatarios WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
?>