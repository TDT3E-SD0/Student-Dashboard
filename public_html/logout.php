<?php
/**
 * User Logout Script
 * 
 * Destroys the user session and redirects to login page
 */

session_start();

// Destroy all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Optional: Log the logout action
// error_log("User session destroyed at " . date('Y-m-d H:i:s'));

// Redirect to login page
header("Location: login.php?logout=success");
exit;
?>
