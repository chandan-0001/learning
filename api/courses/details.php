<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isset($_GET['id'])) {
    jsonResponse(400, "Course ID is required.");
}

$course_id = sanitizeInput($_GET['id']);

$db = new Database();
$conn = $db->connect();

// Get course details
$stmt = $conn->prepare("
    SELECT 
        c.*,
        u.name as instructor_name,
        u.email as instructor_email
    FROM courses c
    LEFT JOIN users u ON c.instructor_id = u.id
    WHERE c.id = :course_id
");

$stmt->bindParam(':course_id', $course_id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    jsonResponse(404, "Course not found.");
}

$course = $stmt->fetch(PDO::FETCH_ASSOC);

// Get modules for this course
$stmt = $conn->prepare("
    SELECT id, title, description, duration, sequence_order 
    FROM modules 
    WHERE course_id = :course_id
    ORDER BY sequence_order
");

$stmt->bindParam(':course_id', $course_id);
$stmt->execute();

$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get accessibility features for this course
$stmt = $conn->prepare("
    SELECT feature 
    FROM course_accessibility_features 
    WHERE course_id = :course_id
");

$stmt->bindParam(':course_id', $course_id);
$stmt->execute();

$accessibility_features = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

jsonResponse(200, "Course details retrieved successfully.", [
    'course' => $course,
    'modules' => $modules,
    'accessibility_features' => $accessibility_features
]);
?>
