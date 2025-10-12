<?php
// src/controllers/ParameterController.php

require_once __DIR__ . '/../models/Parameter.php';

class ParameterController {
    private $parameterModel;

    public function __construct($pdo) {
        $this->parameterModel = new Parameter($pdo);
    }

    public function getParametros() {
        $parametros = $this->parameterModel->getTodosLosParametros();
        return ['success' => true, 'data' => $parametros];
    }

    public function updateParametro($cuartoId, $input) {
        $result = $this->parameterModel->actualizarParametro($cuartoId, $input);
        if ($result) {
            return ['success' => true, 'message' => 'Parámetro actualizado correctamente'];
        }
        return ['success' => false, 'message' => 'No se pudo actualizar el parámetro'];
    }
}