<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

$user = authenticate();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get user preferences
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("
        SELECT preferences 
        FROM user_preferences 
        WHERE user_id = :user_id
    ");
    
    $stmt->bindParam(':user_id', $user['sub']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $preferences = json_decode($stmt->fetch(PDO::FETCH_ASSOC)['preferences'], true);
        jsonResponse(200, "Preferences retrieved.", $preferences);
    } else {
        jsonResponse(200, "No preferences set.", []);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update user preferences
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data)) {
        jsonResponse(400, "No preferences data provided.");
    }
    
    $db = new Database();
    $conn = $db->connect();
    
    try {
        $preferences_json = json_encode($data);
        
        $stmt = $conn->prepare("
            INSERT INTO user_preferences (user_id, preferences) 
            VALUES (:user_id, :preferences)
            ON DUPLICATE KEY UPDATE preferences = :preferences
        ");
        
        $stmt->bindParam(':user_id', $user['sub']);
        $stmt->bindParam(':preferences', $preferences_json);
        $stmt->execute();
        
        jsonResponse(200, "Preferences updated successfully.");
    } catch (PDOException $e) {
        jsonResponse(500, "Error updating preferences: " . $e->getMessage());
    }
} else {
    jsonResponse(405, "Method not allowed.");
}
?>
