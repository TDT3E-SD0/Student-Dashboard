<?php
/**
 * Header Include File
 * 
 * Displays the navigation bar with logo and links
 * Admin Panel link only shown to admin users
 * Requires session to be started before inclusion
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$username = $_SESSION['username'] ?? 'Guest';
$first_name = $_SESSION['first_name'] ?? '';

// Determine current page for active link
$current_page = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - Student Dashboard' : 'Student Dashboard'; ?></title>
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['PHP_SELF'], 'pages/') !== false) ? '../' : ''; ?>assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-wrapper">
            <a href="<?php echo $is_logged_in ? 'dashboard.php' : 'index.php'; ?>" class="logo">TDT3E</a>
            
            <nav>
                <ul>
                    <?php if ($is_logged_in): ?>
                        <li>
                            <a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                                Home
                            </a>
                        </li>
                        <li>
                            <a href="pages/grades.php" class="<?php echo $current_page === 'grades.php' ? 'active' : ''; ?>">
                                Grades
                            </a>
                        </li>
                        <li>
                            <a href="pages/tasks.php" class="<?php echo $current_page === 'tasks.php' ? 'active' : ''; ?>">
                                Tasks
                            </a>
                        </li>
                        <li>
                            <a href="pages/blog.php" class="<?php echo $current_page === 'blog.php' ? 'active' : ''; ?>">
                                Blog
                            </a>
                        </li>
                        <li>
                            <a href="pages/files.php" class="<?php echo $current_page === 'files.php' ? 'active' : ''; ?>">
                                Files
                            </a>
                        </li>
                        <li>
                            <a href="pages/profile.php" class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                                Profile
                            </a>
                        </li>
                        
                        <?php if ($is_admin): ?>
                            <li>
                                <a href="admin/dashboard.php" class="admin-panel-link">
                                    Admin Panel
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li>
                            <a href="logout.php" style="color: #ff6b6b;">
                                Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="login.php" class="<?php echo $current_page === 'login.php' ? 'active' : ''; ?>">
                                Login
                            </a>
                        </li>
                        <li>
                            <a href="register.php" class="<?php echo $current_page === 'register.php' ? 'active' : ''; ?>">
                                Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <main class="container">
