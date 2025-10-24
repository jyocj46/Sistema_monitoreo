<?php
// Archivo: /src/controllers/DestinatarioController.php
require_once __DIR__ . '/../models/Destinatario.php';

class DestinatarioController {
    private $model;

    public function __construct($pdo) {
        $this->model = new Destinatario($pdo);
    }

    // GET /api/destinatarios
    public function getAll() {
        $data = $this->model->getDestinatarios();
        return ['success' => true, 'data' => $data];
    }

    // POST /api/destinatarios
    public function create($input) {
        $email = $input['email'] ?? null;
        $nombre = $input['nombre'] ?? null;

        if (!$email || !$nombre) {
            http_response_code(400);
            return ['success' => false, 'error' => 'Email y nombre son requeridos'];
        }

        $success = $this->model->addDestinatario($email, $nombre);
        return ['success' => $success];
    }

    // DELETE /api/destinatarios/{id}
    public function delete($id) {
        $success = $this->model->deleteDestinatario($id);
        return ['success' => $success];
    }
}
?>