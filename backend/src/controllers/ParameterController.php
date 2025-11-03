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
        
        $data = $this->sanitizeParametroInput($input);

        $result = $this->parameterModel->actualizarParametro((int)$cuartoId, $data);
        if ($result) {
            return ['success' => true, 'message' => 'Parámetro actualizado correctamente'];
        }
        return ['success' => false, 'message' => 'No se pudo actualizar el parámetro'];
    }

    private function sanitizeParametroInput(array $input): array {
        $floatFields = [
            'temp_warn_min_c',
            'temp_warn_max_c',
            'temp_crit_min_c',
            'temp_crit_max_c',
            'hum_warn_min_pct',
            'hum_warn_max_pct',
            'hum_crit_min_pct',
            'hum_crit_max_pct',
        ];

        $data = [];

        foreach ($floatFields as $key) {
            if (array_key_exists($key, $input) && $input[$key] !== '' && $input[$key] !== null) {
                $data[$key] = is_numeric($input[$key]) ? (float)$input[$key] : null;
            }
        }

       
        if (array_key_exists('histéresis_c', $input)) {
            $data['histéresis_c'] = ($input['histéresis_c'] === '' || $input['histéresis_c'] === null)
                ? null
                : (is_numeric($input['histéresis_c']) ? (float)$input['histéresis_c'] : null);
        } elseif (array_key_exists('histeresis_c', $input)) {
           
            $data['histeresis_c'] = ($input['histeresis_c'] === '' || $input['histeresis_c'] === null)
                ? null
                : (is_numeric($input['histeresis_c']) ? (float)$input['histeresis_c'] : null);
        }

       
        if (array_key_exists('habilitado', $input)) {
            
            $val = $input['habilitado'];
            if (is_string($val)) {
                $valLower = strtolower($val);
                if ($valLower === 'true')  { $val = 1; }
                if ($valLower === 'false') { $val = 0; }
            }
            $data['habilitado'] = (int) (bool) $val;
        }

        return $data;
    }
}
