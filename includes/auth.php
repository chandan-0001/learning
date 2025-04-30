<?php
function authenticate() {
    $token = getBearerToken();
    
    if (!$token) {
        jsonResponse(401, "Access denied. No token provided.");
    }
    
    $decoded = validateJWT($token);
    
    if (!$decoded) {
        jsonResponse(401, "Access denied. Invalid token.");
    }
    
    return $decoded;
}
?>
