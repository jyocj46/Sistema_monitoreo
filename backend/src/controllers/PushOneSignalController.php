<?php
// src/controllers/PushOneSignalController.php
require_once __DIR__ . '/../models/PushOneSignal.php';

class PushOneSignalController {
    private $model;

    public function __construct($pdo) {
        $this->model = new PushOneSignal($pdo);
    }

    public function subscribe($input) {
        if (empty($input['playerId'])) {
            return ['success' => false, 'message' => 'Falta playerId'];
        }

        $ok = $this->model->guardarPlayerId($input['playerId']);
        return [
            'success' => $ok,
            'message' => $ok ? 'Suscripción registrada' : 'No se pudo registrar'
        ];
    }

    public function unsubscribe($input) {
        if (empty($input['playerId'])) {
            return ['success' => false, 'message' => 'Falta playerId'];
        }

        $ok = $this->model->eliminarPlayerId($input['playerId']);
        return [
            'success' => $ok,
            'message' => $ok ? 'Suscripción eliminada' : 'No se pudo eliminar'
        ];
    }

    public function getAll() {
        $data = $this->model->obtenerTodos();
        return ['success' => true, 'data' => $data];
    }
}
?>
