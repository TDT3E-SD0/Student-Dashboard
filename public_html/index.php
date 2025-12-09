<?php
/**
 * Landing Page / Index
 * 
 * Redirects users based on authentication status:
 * - If logged in → Dashboard
 * - If not logged in → Login page
 */

session_start();

// Check if user is logged in and active
if (isset($_SESSION['user_id']) && $_SESSION['status'] === 'active') {
    // Redirect to dashboard
    header("Location: dashboard.php");
    exit;
} else {
    // Redirect to login
    header("Location: login.php");
    exit;
}
?>
