<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// This is a simple logout that just invalidates the token client-side
// In a real app, you might want to implement token blacklisting

jsonResponse(200, "Logout successful.");
?>
