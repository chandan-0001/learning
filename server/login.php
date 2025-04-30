<?php
// Database connection parameters
$servername = "localhost"; // Change if necessary
$username = "root"; // Change to your database username
$password = ""; // Change to your database password
$dbname = "chandan"; // Change to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the email and password from the form
$email = $_POST['email'];
$password = $_POST['password'];

// Prepare and bind
$stmt = $conn->prepare("SELECT password FROM login WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

// Check if the email exists
if ($stmt->num_rows > 0) {
    $stmt->bind_result($hashed_password);
    $stmt->fetch();

    // Verify the password
    if (password_verify($password, $hashed_password)) {
        // Password is correct
        echo "<script>alert('Login successful'); window.location.href='loginindex.html';</script>";
    } else {
        // Password is incorrect
        echo "<script>alert('Invalid email or password'); window.history.back();</script>";
    }
} else {
    // Email does not exist
    echo "<script>alert('Invalid email or password'); window.history.back();</script>";
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>