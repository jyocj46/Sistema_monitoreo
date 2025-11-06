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

/**
 * Función helper para cargar archivos de manera segura
 */
function cargarControlador($nombreArchivo, $dbPath = null) {
    // Si tenemos dbPath, generamos la ruta guess
    $guessPath = $dbPath ? str_replace('config/db.php', "controllers/{$nombreArchivo}", $dbPath) : null;
    
    $possiblePaths = array_values(array_unique(array_filter([
        $guessPath,
        __DIR__ . "/../src/controllers/{$nombreArchivo}",
        __DIR__ . "/../../src/controllers/{$nombreArchivo}",
        __DIR__ . "/src/controllers/{$nombreArchivo}",
        '/home1/detponco/src/controllers/' . $nombreArchivo
    ])));
    
    foreach ($possiblePaths as $path) {
        if ($path && file_exists($path)) {
            require_once $path;
            return true;
        }
    }
    
    return false;
}

// Primero cargar la configuración de la base de datos
$possibleDbPaths = [
    __DIR__ . '/../src/config/db.php',
    __DIR__ . '/../../src/config/db.php',
    __DIR__ . '/src/config/db.php',
    '/home1/detponco/src/config/db.php'
];

$db_loaded = false;
$db_path = null;

foreach ($possibleDbPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $db_loaded = true;
        $db_path = $path;
        break;
    }
}

if (!$db_loaded) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo encontrar db.php']);
    exit;
}

// Lista de controladores a cargar
$controladores = [
    'TemperatureController.php',
    'ParameterController.php', 
    'AlertController.php',
    'DestinatarioController.php',
    'UsuarioController.php',
    'PushOneSignalController.php' 
];

foreach ($controladores as $controlador) {
    if (!cargarControlador($controlador, $db_path)) {
        http_response_code(500);
        echo json_encode(['error' => "No se pudo encontrar {$controlador}"]);
        exit;
    }
}

try {
    $req_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $req_path = str_replace('/api', '', $req_path);
    $req_path = $req_path ?: '/';
    $method   = $_SERVER['REQUEST_METHOD'];

    
        $controller        = new TemperatureController($pdo);
        $paramController   = new ParameterController($pdo);
        $usuarioController = new UsuarioController(); // o new UsuarioController($pdo) si tu clase lo requiere
        $alertController   = new AlertController($pdo);
        $destController    = new DestinatarioController($pdo);
        $pushCtrl = new PushOneSignalController($pdo);

    switch (true) {

        case $req_path === '/login' && $method === 'POST':
            $usuarioController->login(); 
            break;
        
        case $req_path === '/lecturas' && $method === 'GET':
            $sensorId    = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : null;
            $cuartoId    = isset($_GET['cuarto_id']) ? (int)$_GET['cuarto_id'] : null;
            $limit       = isset($_GET['limit']) ? max(1, min((int)$_GET['limit'], 1000)) : 200;
            $fechaInicio = $_GET['fecha_inicio'] ?? null;
            $fechaFin    = $_GET['fecha_fin'] ?? null;
            $horaInicio  = $_GET['hora_inicio'] ?? null; 
            $horaFin     = $_GET['hora_fin'] ?? null;    
            $sortOrder   = $_GET['sort'] ?? 'DESC';

            $result = $controller->getLecturas($sensorId, $cuartoId, $limit, $fechaInicio, $fechaFin, $horaInicio, $horaFin, $sortOrder);
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
            $cuartoId    = isset($_GET['cuarto_id']) ? (int)$_GET['cuarto_id'] : null;
            $fechaInicio = $_GET['fecha_inicio'] ?? null;
            $fechaFin    = $_GET['fecha_fin'] ?? null;
            $horaInicio  = $_GET['hora_inicio'] ?? null;
            $horaFin     = $_GET['hora_fin'] ?? null;

            $result = $controller->getPromedios($fechaInicio, $fechaFin, $cuartoId, $horaInicio, $horaFin);
            echo json_encode($result);
            break;

        case $req_path === '/ultimas' && $method === 'GET':
            $by = $_GET['by'] ?? 'cuarto';
            $result = $controller->getUltimas($by);
            echo json_encode($result);
            break;

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

        case $req_path === '/alertas/activas' && $method === 'GET':
            if (!$alertController) {
                http_response_code(404);
                echo json_encode(['error' => 'AlertController no disponible']);
                break;
            }
            $result = $alertController->getActivas();
            echo json_encode($result);
            break;

        case $req_path === '/destinatarios' && $method === 'GET':
            if (!$destController) { http_response_code(404); break; }
            echo json_encode($destController->getAll());
            break;

        case $req_path === '/destinatarios' && $method === 'POST':
            if (!$destController) { http_response_code(404); break; }
            $input = json_decode(file_get_contents('php://input'), true);
            echo json_encode($destController->create($input));
            break;

        case preg_match('/^\/destinatarios\/(\d+)$/', $req_path, $matches) && $method === 'DELETE':
            if (!$destController) { http_response_code(404); break; }
            $id = (int)$matches[1];
            echo json_encode($destController->delete($id));
            break;

        /*case $req_path === '/debug/wa' && $method === 'GET':
            // Cambiar el header a texto plano para ver el log
            header('Content-Type: text/plain; charset=utf-8');
            echo "--- INICIANDO PRUEBA DE WHATSAPP ---\n\n";

            // ¡Modificación temporal! Cambia 'error_log' por 'echo' en el Helper
            echo "AVISO: Para ver el log de cURL, debes cambiar temporalmente todas las llamadas 'error_log(' por 'echo (' en WhatsappHelper.php\n\n";

            $dummy = [
                'cuarto_nombre'   => 'TEST',
                'variable'        => 'Temperatura',
                'valor_medido'    => '99',
                'rango_esperado'  => '1-2',
            ];

            // Llamar al helper (cuyos 'error_log' ahora son 'echo')
            WhatsAppHelper::enviarMensajeAlerta($dummy, $pdo);

            echo "\n--- PRUEBA FINALIZADA ---\n";
            exit; */
            
        case $req_path === '/subscribe_push' && $method === 'POST':
            $input = json_decode(file_get_contents('php://input'), true);            
            echo json_encode($pushCtrl->subscribe($input));
            break;

        case $req_path === '/unsubscribe_push' && $method === 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            echo json_encode($pushCtrl->unsubscribe($input));
            break;        

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint no encontrado: ' . $req_path]);
    }

} catch (Throwable $e) {
    // Captura también fatales en PHP >=7
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
