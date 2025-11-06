<?php
// src/controllers/UsuarioController.php

/**
 * Autoloader mínimo para Firebase\JWT sin Composer
 */
spl_autoload_register(function ($class) { 
    $prefix = 'Firebase\\JWT\\';
    $base_dir = __DIR__ . '/../helpers/php-jwt/src/';
   
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; 
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Usuario.php';

class UsuarioController {

    public function login() {
        // === 1) Headers y CORS ===
        header("Access-Control-Allow-Origin: *"); // Ajusta a tu dominio en prod
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

        // Responder preflight rápidamente
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            return;
        }

        // === 2) Log de depuración ===
        // Cambia esta ruta si prefieres otra carpeta (ej: __DIR__ . '/../../storage/logs/debug_login.log')
        $log_file = __DIR__ . '/../debug_login.log';
        file_put_contents($log_file, "===== NUEVA PETICIÓN DE LOGIN (" . date('Y-m-d H:i:s') . " UTC) =====\n", FILE_APPEND);
        file_put_contents($log_file, "Método: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n", FILE_APPEND);

        // === 3) Cargar JSON del body ===
        $raw = file_get_contents("php://input");
        file_put_contents($log_file, "RAW body: " . $raw . "\n", FILE_APPEND);

        $data = json_decode($raw);
        file_put_contents($log_file, "JSON decodificado: " . json_encode($data) . "\n", FILE_APPEND);

        // === 4) Validaciones tempranas ===
        $email = isset($data->email) ? trim($data->email) : '';
        $password = isset($data->password) ? (string)$data->password : '';

        if ($email === '' || $password === '') {
            file_put_contents($log_file, "Error 400: email o password vacíos.\n", FILE_APPEND);
            http_response_code(400);
            echo json_encode(["message" => "Correo y contraseña son requeridos."]);
            return;
        }

        // === 5) Búsqueda de usuario ===
        global $pdo;
        $usuarioModel = new Usuario($pdo);
        $user = $usuarioModel->findByEmail($email);

        // OJO: json_encode en arrays asociativos puede fallar con binarios. Forzamos campos clave solamente.
        $user_min = $user ? [
            "id" => $user['id'] ?? null,
            "correo" => $user['correo'] ?? null,
            "activo" => $user['activo'] ?? null,
            "tiene_contrasenia" => isset($user['contrasenia']) ? (strlen((string)$user['contrasenia']) > 0 ? 'sí' : 'no') : 'no'
        ] : null;
        file_put_contents($log_file, "Usuario encontrado (resumen): " . json_encode($user_min) . "\n", FILE_APPEND);

        // === 6) Depuración de password_verify ===
        if ($user) {
            $hash_de_bd = (string)($user['contrasenia'] ?? '');
            $password_recibida = $password;
            $es_valido = false;

            // Logueamos longitudes para evitar exponer credenciales completas en producción
            file_put_contents($log_file, "Hash BD (len=" . strlen($hash_de_bd) . "): $hash_de_bd\n", FILE_APPEND);
            file_put_contents($log_file, "Pass recibida (len=" . strlen($password_recibida) . "): $password_recibida\n", FILE_APPEND);

            // Verificación real
            if ($hash_de_bd !== '') {
                $es_valido = password_verify($password_recibida, $hash_de_bd);
            }
            file_put_contents($log_file, "password_verify(): " . ($es_valido ? 'TRUE' : 'FALSE') . "\n", FILE_APPEND);

            // Sugerencia de rehash (por si el cost/algoritmo cambió)
            if ($es_valido && password_needs_rehash($hash_de_bd, PASSWORD_DEFAULT)) {
                file_put_contents($log_file, "Aviso: password_needs_rehash() = TRUE (considerar actualizar hash).\n", FILE_APPEND);
            }
        } else {
            file_put_contents($log_file, "Usuario no encontrado (NULL/false en findByEmail).\n", FILE_APPEND);
        }

        // === 7) Validación final de acceso ===
        // Nota: campos en BD asumidos: 'activo' (1/0), 'contrasenia' (hash), 'correo'
        $activo = (int)($user['activo'] ?? 0);
        $login_ok = ($user && $activo === 1 && isset($user['contrasenia']) && password_verify($password, (string)$user['contrasenia']));

        if (!$login_ok) {
            file_put_contents($log_file, "Error 401: Validación falló (user=" . ($user ? 'sí' : 'no') . ", activo=$activo, verify=" . ((isset($user['contrasenia']) && password_verify($password, (string)$user['contrasenia'])) ? 'TRUE' : 'FALSE') . ").\n", FILE_APPEND);
            http_response_code(401);
            echo json_encode(["message" => "Credenciales inválidas o usuario inactivo."]);
            return;
        }

        file_put_contents($log_file, "Éxito 200: Validación OK. Generando JWT...\n", FILE_APPEND);

        // === 8) JWT ===
        // RECOMENDADO: Mover a variable de entorno
        $secret_key = "bV$]2=uB9?SUx}vVAzEe5iLS/3rGC*-4Av551{2D.\ZkN!41-X";
        $issuer_claim   = "drover.detpon.com";
        $audience_claim = "drover.detpon.com";
        $issuedat_claim = time();
        $expire_claim   = $issuedat_claim + 3600; // 1 hora

        $payload = [
            "iss"  => $issuer_claim,
            "aud"  => $audience_claim,
            "iat"  => $issuedat_claim,
            "exp"  => $expire_claim,
            "data" => [
                "id"    => $user['id'],
                // OJO: en la BD usas 'correo'; en el body recibes 'email'
                "email" => $user['correo'],
                "rol"   => $user['rol'] ?? null,
            ]
        ];

        // Puedes usar cualquiera de estas dos formas equivalentes:
        // $token = JWT::encode($payload, $secret_key, 'HS256');
        // o con Key (más explícito en nuevas versiones):
        $token = JWT::encode($payload, $secret_key, 'HS256');

        // === 9) Respuesta OK (sin contraseña) ===
        $user_safe = $user;
        unset($user_safe['contrasenia']);

        http_response_code(200);
        echo json_encode([
            "message" => "Inicio de sesión exitoso.",
            "token"   => $token,
            "user"    => $user_safe
        ]);

        file_put_contents($log_file, "JWT emitido. Login completado.\n", FILE_APPEND);
    }
}
