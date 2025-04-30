<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, "Method not allowed.");
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->email) || empty($data->password)) {
    jsonResponse(400, "Email and password are required.");
}

$email = sanitizeInput($data->email);
$password = sanitizeInput($data->password);

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("
    SELECT id, name, email, password, user_type 
    FROM users 
    WHERE email = :email
");

$stmt->bindParam(':email', $email);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    jsonResponse(401, "Invalid email or password.");
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!password_verify($password, $user['password'])) {
    jsonResponse(401, "Invalid email or password.");
}

// Get user's accessibility needs
$stmt = $conn->prepare("
    SELECT need_type 
    FROM user_accessibility_needs 
    WHERE user_id = :user_id
");

$stmt->bindParam(':user_id', $user['id']);
$stmt->execute();

$needs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Generate JWT
$jwt = generateJWT($user['id'], $user['email'], $user['user_type']);

jsonResponse(200, "Login successful.", [
    'token' => $jwt,
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'user_type' => $user['user_type'],
        'accessibility_needs' => $needs
    ]
]);
?>
