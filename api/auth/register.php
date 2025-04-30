<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, "Method not allowed.");
}

$data = json_decode(file_get_contents("php://input"));

if (
    empty($data->name) ||
    empty($data->email) ||
    empty($data->password) ||
    empty($data->user_type)
) {
    jsonResponse(400, "Missing required fields.");
}

// Validate email
if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(400, "Invalid email format.");
}

// Validate password
if (!validatePassword($data->password)) {
    jsonResponse(400, "Password must be at least 8 characters with uppercase, lowercase, number and special character.");
}

$name = sanitizeInput($data->name);
$email = sanitizeInput($data->email);
$password = password_hash($data->password, PASSWORD_BCRYPT);
$user_type = sanitizeInput($data->user_type);
$accessibility_needs = isset($data->accessibility_needs) ? $data->accessibility_needs : [];

$db = new Database();
$conn = $db->connect();

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
$stmt->bindParam(':email', $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    jsonResponse(409, "Email already exists.");
}

// Insert user
try {
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("
        INSERT INTO users (name, email, password, user_type, created_at) 
        VALUES (:name, :email, :password, :user_type, NOW())
    ");
    
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':user_type', $user_type);
    $stmt->execute();
    
    $user_id = $conn->lastInsertId();
    
    // Insert accessibility needs if any
    if (!empty($accessibility_needs)) {
        $stmt = $conn->prepare("
            INSERT INTO user_accessibility_needs (user_id, need_type) 
            VALUES (:user_id, :need_type)
        ");
        
        foreach ($accessibility_needs as $need) {
            $need = sanitizeInput($need);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':need_type', $need);
            $stmt->execute();
        }
    }
    
    $conn->commit();
    
    // Generate JWT
    $jwt = generateJWT($user_id, $email, $user_type);
    
    jsonResponse(201, "User created successfully.", [
        'token' => $jwt,
        'user' => [
            'id' => $user_id,
            'name' => $name,
            'email' => $email,
            'user_type' => $user_type,
            'accessibility_needs' => $accessibility_needs
        ]
    ]);
    
} catch (PDOException $e) {
    $conn->rollBack();
    jsonResponse(500, "Error creating user: " . $e->getMessage());
}
?>