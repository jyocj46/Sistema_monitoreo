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

// MANTENER la búsqueda flexible de archivos
$possible_paths = [
    __DIR__ . '/../src/config/db.php',
    __DIR__ . '/../../src/config/db.php',
    __DIR__ . '/src/config/db.php',
    '/home1/detponco/src/config/db.php'
];

$db_loaded = false;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $db_loaded = true;
        break;
    }
}

if (!$db_loaded) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo encontrar db.php']);
    exit;
}

// Cargar controlador
$controller_path = str_replace('config/db.php', 'controllers/TemperatureController.php', $path);
if (file_exists($controller_path)) {
    require_once $controller_path;
} else {
    $controller_paths = [
        __DIR__ . '/../src/controllers/TemperatureController.php',
        __DIR__ . '/../../src/controllers/TemperatureController.php',
        __DIR__ . '/src/controllers/TemperatureController.php',
        '/home1/detponco/src/controllers/TemperatureController.php'
    ];
    
    $controller_loaded = false;
    foreach ($controller_paths as $ctrl_path) {
        if (file_exists($ctrl_path)) {
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
}

try {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = str_replace('/api', '', $path);
    $path = $path ?: '/';
    $method = $_SERVER['REQUEST_METHOD'];

    $controller = new TemperatureController($pdo);

    switch(true) {
        case $path === '/lecturas' && $method === 'GET':
            // Parámetros existentes
            $sensorId = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : null;
            $cuartoId = isset($_GET['cuarto_id']) ? (int)$_GET['cuarto_id'] : null;
            $limit = isset($_GET['limit']) ? max(1, min((int)$_GET['limit'], 1000)) : 200;
            $fechaInicio = $_GET['fecha_inicio'] ?? null;
            $fechaFin = $_GET['fecha_fin'] ?? null;
            $result = $controller->getLecturas($sensorId, $cuartoId, $limit, $fechaInicio, $fechaFin);
            $sortOrder = $_GET['sort'] ?? 'DESC';
            $result = $controller->getLecturas($sensorId, $cuartoId, $limit, $fechaInicio, $fechaFin, $sortOrder);
            echo json_encode($result);
            break;
            
        case $path === '/ultimas' && $method === 'GET':
            $by = $_GET['by'] ?? 'cuarto'; // Mantener flexible
            $result = $controller->getUltimas($by);
            echo json_encode($result);
            break;
            
        case $path === '/cuartos' && $method === 'GET':
             $result = $controller->getCuartos();
             echo json_encode($result);
             break;   

        case $path === '/lecturas' && $method === 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos JSON inválidos']);
                break;
            }
            $result = $controller->insertarLectura($input);
            echo json_encode($result);
            break;
            
        case $path === '/estadisticas' && $method === 'GET':
            $cuartoId = isset($_GET['cuarto_id']) ? (int)$_GET['cuarto_id'] : null;
            $periodo = $_GET['periodo'] ?? 'DAY';
            $result = $controller->getEstadisticas($cuartoId, $periodo);
            echo json_encode($result);
            break;  
        
        case $path === '/lecturas/grafica' && $method === 'GET':
            $result = $controller->getLecturasParaGrafica();
            echo json_encode($result);
            break;    
            
        case $path === '/lecturas/promedios' && $method === 'GET':
        $result = $controller->getPromedios();
        echo json_encode($result);
        break;    

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint no encontrado: ' . $path]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}