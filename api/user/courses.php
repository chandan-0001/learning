<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

$user = authenticate();

$db = new Database();
$conn = $db->connect();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get user's enrolled courses
    $stmt = $conn->prepare("
        SELECT 
            c.id, 
            c.title, 
            c.description, 
            c.category, 
            c.level,
            c.image_url,
            e.enrolled_at,
            e.completion_percentage
        FROM courses c
        JOIN enrollments e ON c.id = e.course_id
        WHERE e.user_id = :user_id
    ");
    
    $stmt->bindParam(':user_id', $user['sub']);
    $stmt->execute();
    
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse(200, "User courses retrieved.", $courses);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Enroll in a course
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->course_id)) {
        jsonResponse(400, "Course ID is required.");
    }
    
    $course_id = sanitizeInput($data->course_id);
    
    // Check if already enrolled
    $stmt = $conn->prepare("
        SELECT id 
        FROM enrollments 
        WHERE user_id = :user_id AND course_id = :course_id
    ");
    
    $stmt->bindParam(':user_id', $user['sub']);
    $stmt->bindParam(':course_id', $course_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        jsonResponse(409, "Already enrolled in this course.");
    }
    
    // Enroll user
    $stmt = $conn->prepare("
        INSERT INTO enrollments (user_id, course_id, enrolled_at) 
        VALUES (:user_id, :course_id, NOW())
    ");
    
    $stmt->bindParam(':user_id', $user['sub']);
    $stmt->bindParam(':course_id', $course_id);
    $stmt->execute();
    
    jsonResponse(201, "Enrolled in course successfully.");
} else {
    jsonResponse(405, "Method not allowed.");
}
?>
