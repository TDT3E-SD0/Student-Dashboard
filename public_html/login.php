<?php
/**
 * User Login Page
 * 
 * Validates credentials and checks account status
 * Only allows login if status == 'active'
 * Shows approval message if status == 'pending'
 */

session_start();

// If user is already logged in and active, redirect to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['status'] === 'active') {
    header("Location: dashboard.php");
    exit;
}

// Include database connection
require_once '../private/config/db_connect.php';

$errors = [];
$email = '';
$info_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If validation passes, check credentials
    if (empty($errors)) {
        try {
            // Fetch user by email
            $stmt = $pdo->prepare("
                SELECT id, username, password, status, first_name, last_name 
                FROM users 
                WHERE email = ? AND status IN ('active', 'pending')
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Password is correct - now check status
                
                if ($user['status'] === 'pending') {
                    // Account pending approval
                    $info_message = "Your account is pending administrator approval. You will be able to log in once approved.";
                } elseif ($user['status'] === 'active') {
                    // Account is active - allow login
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $email;
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['status'] = $user['status'];
                    $_SESSION['login_time'] = time();
                    
                    // Optional: Log successful login
                    // error_log("User {$user['id']} logged in successfully");
                    
                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit;
                }
            } else {
                // Invalid credentials
                $errors[] = "Invalid email or password";
                // Don't specify which field is wrong (security best practice)
            }
        } catch (PDOException $e) {
            $errors[] = "Login failed. Please try again later.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-wrapper">
            <h1>Student Dashboard</h1>
            <h2>Login</h2>
            
            <?php if (!empty($info_message)): ?>
                <div class="alert alert-info">
                    <strong>Account Pending Approval</strong>
                    <p><?php echo htmlspecialchars($info_message); ?></p>
                    <p>If you believe this is an error, please contact support.</p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <strong>Login Failed:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" novalidate>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($email); ?>"
                        required
                        autofocus
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password"
                        required
                    >
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="forgot-password.php">Forgot your password?</a></p>
            </div>
        </div>
    </div>
</body>
</html>
