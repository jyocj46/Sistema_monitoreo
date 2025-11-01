<?php
// src/controllers/UsuarioController.php

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
        // 1. Establecer headers para responder con JSON
        header("Access-Control-Allow-Origin: *"); // Permite CORS (ajusta en producción)
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->email) || empty($data->password)) {
            http_response_code(400); // Bad Request
            echo json_encode(["message" => "Correo y contraseña son requeridos."]);
            return;
        }

        global $pdo;
        $usuarioModel = new Usuario($pdo);
        $user = $usuarioModel->findByEmail($data->email);

        
        if (!$user || $user['activo'] != 1 || !password_verify($data->password, $user['contrasenia'])) {
            http_response_code(401); 
            echo json_encode(["message" => "Credenciales inválidas o usuario inactivo."]);
            return;
        }
       
        $secret_key = "bV$]2=uB9?SUx}vVAzEe5iLS/3rGC*-4Av551{2D.\ZkN!41-X"; 
        $issuer_claim = "drover.detpon.com"; 
        $audience_claim = "drover.detpon.com";
        $issuedat_claim = time(); 
        $expire_claim = $issuedat_claim + 3600; 

        $payload = [
            "iss" => $issuer_claim,
            "aud" => $audience_claim,
            "iat" => $issuedat_claim,
            "exp" => $expire_claim,
            "data" => [
                "id" => $user['id'],
                "email" => $user['correo'],
                "rol" => $user['rol']
            ]
        ];
        
        $token = JWT::encode($payload, $secret_key, 'HS256');

        // 7. Preparar datos de usuario para devolver (¡SIN LA CONTRASEÑA!)
        unset($user['contrasenia']);

        // 8. Enviar la respuesta
        http_response_code(200); // OK
        echo json_encode([
            "message" => "Inicio de sesión exitoso.",
            "token" => $token,
            "user" => $user // Envía los datos del usuario
        ]);
    }
}
?>