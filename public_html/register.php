<?php
/**
 * User Registration Page
 * 
 * Allows new users to register with email and password
 * Password is hashed using bcrypt
 * Default status: 'pending' (awaiting admin approval)
 */

session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['status'] === 'active') {
    header("Location: dashboard.php");
    exit;
}

// Include database connection
require_once '../private/config/db_connect.php';

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    } elseif (strlen($username) > 50) {
        $errors[] = "Username must not exceed 50 characters";
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, dots, hyphens, and underscores";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, and one number";
    }
    
    if ($password !== $password_confirm) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($first_name)) {
        $errors[] = "First name is required";
    } elseif (strlen($first_name) > 100) {
        $errors[] = "First name must not exceed 100 characters";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    } elseif (strlen($last_name) > 100) {
        $errors[] = "Last name must not exceed 100 characters";
    }
    
    // Check if username already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "Username or email already exists";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: Unable to check username availability";
            error_log("Registration check error: " . $e->getMessage());
        }
    }
    
    // If no errors, insert user
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, first_name, last_name, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([$username, $email, $hashed_password, $first_name, $last_name]);
            
            $success = true;
            // Clear form data
            $username = $email = $password = $password_confirm = $first_name = $last_name = '';
            
        } catch (PDOException $e) {
            $errors[] = "Registration failed. Please try again later.";
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Student Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-wrapper">
            <h1>Create Account</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>Registration Successful!</strong>
                    <p>Your account has been created and is pending admin approval.</p>
                    <p>You will be able to log in once an administrator approves your account.</p>
                    <p><a href="login.php">Go to Login</a></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <strong>Registration Failed:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
                <form method="POST" action="register.php" novalidate>
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input 
                            type="text" 
                            id="first_name" 
                            name="first_name" 
                            value="<?php echo htmlspecialchars($first_name ?? ''); ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input 
                            type="text" 
                            id="last_name" 
                            name="last_name" 
                            value="<?php echo htmlspecialchars($last_name ?? ''); ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?php echo htmlspecialchars($username ?? ''); ?>"
                            placeholder="3-50 characters (letters, numbers, ._-)"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($email ?? ''); ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password"
                            placeholder="Min 8 characters, 1 uppercase, 1 lowercase, 1 number"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Confirm Password</label>
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm"
                            required
                        >
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Register</button>
                </form>
                
                <p class="auth-footer">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
