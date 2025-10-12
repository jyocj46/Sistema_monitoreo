<?php
// /api/index.php

header('Content-Type: application/json; charset=utf-8');

// CORS
header('Access-Control-Allow-Origin: https://drover.detpon.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ===========================
   1) Cargar db.php (búsqueda flexible)
   =========================== */
$possible_paths = [
    __DIR__ . '/../src/config/db.php',
    __DIR__ . '/../../src/config/db.php',
    __DIR__ . '/src/config/db.php',
    '/home1/detponco/src/config/db.php'
];

$db_loaded = false;
$db_path   = null;

foreach ($possible_paths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $db_loaded = true;
        $db_path   = $p;
        break;
    }
}

if (!$db_loaded) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo encontrar db.php']);
    exit;
}

/* ===========================
   2) Cargar TemperatureController
   =========================== */
$controller_path_guess = $db_path
    ? str_replace('config/db.php', 'controllers/TemperatureController.php', $db_path)
    : null;

$controller_paths = array_values(array_unique(array_filter([
    $controller_path_guess,
    __DIR__ . '/../src/controllers/TemperatureController.php',
    __DIR__ . '/../../src/controllers/TemperatureController.php',
    __DIR__ . '/src/controllers/TemperatureController.php',
    '/home1/detponco/src/controllers/TemperatureController.php'
])));

$controller_loaded = false;
foreach ($controller_paths as $ctrl_path) {
    if ($ctrl_path && file_exists($ctrl_path)) {
        require_once $ctrl_path;
        $controller_loaded = true;
        break;
    }
}
if (!$controller_loaded) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo encontrar TemperatureController.php']);
    exit;
}

/* ===========================
   3) Cargar ParameterController (NUEVO)
   =========================== */
$param_controller_guess = $db_path
    ? str_replace('config/db.php', 'controllers/ParameterController.php', $db_path)
    : null;

$param_controller_paths = array_values(array_unique(array_filter([
    $param_controller_guess,
    __DIR__ . '/../src/controllers/ParameterController.php',
    __DIR__ . '/../../src/controllers/ParameterController.php',
    __DIR__ . '/src/controllers/ParameterController.php',
    '/home1/detponco/src/controllers/ParameterController.php'
])));

$param_loaded = false;
foreach ($param_controller_paths as $pctrl) {
    if ($pctrl && file_exists($pctrl)) {
        require_once $pctrl;
        $param_loaded = true;
        break;
    }
}
if (!$param_loaded) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo encontrar ParameterController.php']);
    exit;
}

/* ===========================
   4) Router
   =========================== */
try {
    $req_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $req_path = str_replace('/api', '', $req_path);
    $req_path = $req_path ?: '/';
    $method   = $_SERVER['REQUEST_METHOD'];

    // Instancias de controladores
    // Nota: $pdo debe venir de db.php
    $controller      = new TemperatureController($pdo);
    $paramController = new ParameterController($pdo);

    switch (true) {

        /* ====== Lecturas ====== */
        case $req_path === '/lecturas' && $method === 'GET':
            $sensorId    = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : null;
            $cuartoId    = isset($_GET['cuarto_id']) ? (int)$_GET['cuarto_id'] : null;
            $limit       = isset($_GET['limit']) ? max(1, min((int)$_GET['limit'], 1000)) : 200;
            $fechaInicio = $_GET['fecha_inicio'] ?? null;
            $fechaFin    = $_GET['fecha_fin'] ?? null;
            $sortOrder   = $_GET['sort'] ?? 'DESC';

            // ✅ Deja solo UNA llamada
            $result = $controller->getLecturas($sensorId, $cuartoId, $limit, $fechaInicio, $fechaFin, $sortOrder);
            echo json_encode($result);
            break;

        case $req_path === '/lecturas' && $method === 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos JSON inválidos']);
                break;
            }
            $result = $controller->insertarLectura($input);
            echo json_encode($result);
            break;

        case $req_path === '/lecturas/grafica' && $method === 'GET':
            $result = $controller->getLecturasParaGrafica();
            echo json_encode($result);
            break;

        case $req_path === '/lecturas/promedios' && $method === 'GET':
            $result = $controller->getPromedios();
            echo json_encode($result);
            break;

        /* ====== Últimas ====== */
        case $req_path === '/ultimas' && $method === 'GET':
            $by = $_GET['by'] ?? 'cuarto';
            $result = $controller->getUltimas($by);
            echo json_encode($result);
            break;

        /* ====== Cuartos / Estadísticas ====== */
        case $req_path === '/cuartos' && $method === 'GET':
            $result = $controller->getCuartos();
            echo json_encode($result);
            break;

        case $req_path === '/estadisticas' && $method === 'GET':
            $cuartoId = isset($_GET['cuarto_id']) ? (int)$_GET['cuarto_id'] : null;
            $periodo  = $_GET['periodo'] ?? 'DAY';
            $result   = $controller->getEstadisticas($cuartoId, $periodo);
            echo json_encode($result);
            break;

        /* ====== Parámetros (NUEVO) ====== */
        case $req_path === '/parametros' && $method === 'GET':
            $result = $paramController->getParametros();
            echo json_encode($result);
            break;

        case preg_match('/^\/parametros\/(\d+)$/', $req_path, $matches) && $method === 'POST':
            $cuartoId = (int)$matches[1];
            $input    = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos JSON inválidos']);
                break;
            }
            $result = $paramController->updateParametro($cuartoId, $input);
            echo json_encode($result);
            break;

        /* ====== Default ====== */
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint no encontrado: ' . $req_path]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
