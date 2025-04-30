<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function generateJWT($user_id, $email, $user_type) {
    $issued_at = time();
    $expiration_time = $issued_at + (60 * 60); // valid for 1 hour
    
    $payload = array(
        "iat" => $issued_at,
        "exp" => $expiration_time,
        "sub" => $user_id,
        "email" => $email,
        "type" => $user_type
    );
    
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

function validateJWT($jwt) {
    try {
        $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        return false;
    }
}

function getAuthorizationHeader() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

function getBearerToken() {
    $headers = getAuthorizationHeader();
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function validatePassword($password) {
    $min_length = 8;
    $has_upper = preg_match('/[A-Z]/', $password);
    $has_lower = preg_match('/[a-z]/', $password);
    $has_number = preg_match('/\d/', $password);
    $has_special = preg_match('/[^a-zA-Z\d]/', $password);
    
    return strlen($password) >= $min_length && 
           $has_upper && 
           $has_lower && 
           $has_number && 
           $has_special;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function jsonResponse($status, $message, $data = null) {
    http_response_code($status);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}
?>