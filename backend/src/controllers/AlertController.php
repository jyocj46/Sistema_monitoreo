<?php
// /src/controllers/AlertController.php

require_once __DIR__ . '/../models/Alerta.php';

class AlertController {
    private $alertaModel;

    public function __construct($pdo) {
        $this->alertaModel = new Alerta($pdo);
    }

    public function getActivas() {
        $alertas = $this->alertaModel->getAlertasActivas();
        return ['success' => true, 'data' => $alertas];
    }
}