<?php
$servername = "localhost"; // or your server name
$username = "root"; // your database username
$password = ""; // your database password
$dbname = "chandan"; // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $conn->real_escape_string(trim($_POST['name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $conn->real_escape_string(trim($_POST['password']));
    $type = $conn->real_escape_string(trim($_POST['type']));
    $accessibility = isset($_POST['accessibility']) ? $_POST['accessibility'] : [];

    // Validate password (basic example)
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
        die("Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.");
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL statement to insert data
    $sql = "INSERT INTO login (name, email, password, type, accessibility) VALUES (?, ?, ?, ?, ?)";
    
    // Prepare statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    // Convert accessibility array to a comma-separated string
    $accessibility_string = implode(',', $accessibility);

    // Bind parameters
    $stmt->bind_param("sssss", $name, $email, $hashed_password, $type, $accessibility_string);

    // Execute statement
    if ($stmt->execute()) {
        echo "Account created successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close statement
    $stmt->close();
}

// Close connection
$conn->close();
?>