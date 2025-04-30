<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Authenticate the user
$user = authenticate();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, "Method not allowed. Use POST.");
}

// Get and validate input data
$data = json_decode(file_get_contents("php://input"));

if (empty($data->course_id)) {
    jsonResponse(400, "Course ID is required.");
}

$course_id = sanitizeInput($data->course_id);

// Database connection
$db = new Database();
$conn = $db->connect();

try {
    // Check if course exists
    $stmt = $conn->prepare("SELECT id FROM courses WHERE id = :course_id");
    $stmt->bindParam(':course_id', $course_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        jsonResponse(404, "Course not found.");
    }

    // Check if user is already enrolled
    $stmt = $conn->prepare("
        SELECT id 
        FROM enrollments 
        WHERE user_id = :user_id AND course_id = :course_id
    ");
    $stmt->bindParam(':user_id', $user['sub']);
    $stmt->bindParam(':course_id', $course_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        jsonResponse(409, "You are already enrolled in this course.");
    }

    // Begin transaction
    $conn->beginTransaction();

    // Enroll the user
    $stmt = $conn->prepare("
        INSERT INTO enrollments (user_id, course_id, enrolled_at) 
        VALUES (:user_id, :course_id, NOW())
    ");
    $stmt->bindParam(':user_id', $user['sub']);
    $stmt->bindParam(':course_id', $course_id);
    $stmt->execute();

    // Initialize progress for all modules in this course
    $stmt = $conn->prepare("
        INSERT INTO user_progress (user_id, course_id, module_id, completed, progress_percentage)
        SELECT :user_id, :course_id, id, FALSE, 0
        FROM modules
        WHERE course_id = :course_id
    ");
    $stmt->bindParam(':user_id', $user['sub']);
    $stmt->bindParam(':course_id', $course_id);
    $stmt->execute();

    // Record enrollment activity
    $stmt = $conn->prepare("
        INSERT INTO activities (user_id, activity_type, activity_data)
        VALUES (:user_id, 'course_enrolled', :activity_data)
    ");
    $activity_data = json_encode(['course_id' => $course_id]);
    $stmt->bindParam(':user_id', $user['sub']);
    $stmt->bindParam(':activity_data', $activity_data);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    // Get course details for response
    $stmt = $conn->prepare("
        SELECT id, title, description, category, level, image_url
        FROM courses
        WHERE id = :course_id
    ");
    $stmt->bindParam(':course_id', $course_id);
    $stmt->execute();
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    jsonResponse(201, "Successfully enrolled in course.", [
        'course' => $course,
        'enrolled_at' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    // Roll back transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    jsonResponse(500, "Database error: " . $e->getMessage());
}
?>
