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
        
        $nombre = $input['nombre'] ?? null;
        $tipo = $input['tipo'] ?? null;
        $valor = $input['valor'] ?? null;

        if (!$nombre || !$tipo || !$valor) {
            http_response_code(400);
            return ['success' => false, 'error' => 'Nombre, Tipo y Valor son requeridos'];
        }

        $success = $this->model->addDestinatario($nombre, $tipo, $valor);
        return ['success' => $success];
    }

    // DELETE /api/destinatarios/{id}
    public function delete($id) {
        $success = $this->model->deleteDestinatario($id);
        return ['success' => $success];
    }
}
?>