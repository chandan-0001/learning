<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

$db = new Database();
$conn = $db->connect();

// Get query parameters
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;

// Build query
$query = "SELECT 
    c.id, 
    c.title, 
    c.description, 
    c.category, 
    c.level, 
    c.modules_count,
    c.image_url,
    COUNT(e.user_id) as enrolled_count
FROM courses c
LEFT JOIN enrollments e ON c.id = e.course_id
WHERE 1=1";

$params = [];

if ($category && $category !== 'All') {
    $query .= " AND c.category = :category";
    $params[':category'] = $category;
}

if ($search) {
    $query .= " AND (c.title LIKE :search OR c.description LIKE :search OR c.category LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " GROUP BY c.id";

$stmt = $conn->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for filters
$stmt = $conn->prepare("SELECT DISTINCT category FROM courses");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

jsonResponse(200, "Courses retrieved successfully.", [
    'courses' => $courses,
    'categories' => $categories
]);
?>
