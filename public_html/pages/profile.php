<?php
/**
 * Student Profile Page
 * 
 * Features:
 * - View and edit profile information
 * - Change password
 * - Upload profile picture
 * - View account settings
 */

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['status'] !== 'active') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Profile";

// Include header
require_once '../../private/includes/header.php';

// Database connection
require_once '../../private/config/db_connect.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Fetch user profile
try {
    $stmt = $pdo->prepare("
        SELECT * FROM users WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Fetch user error: " . $e->getMessage());
    $user = [];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    
    // Validation
    if (empty($first_name) || empty($last_name)) {
        $errors[] = "First and last name are required";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users
                SET first_name = ?, last_name = ?, bio = ?, phone = ?, city = ?, country = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $first_name,
                $last_name,
                $bio ?: null,
                $phone ?: null,
                $city ?: null,
                $country ?: null,
                $user_id
            ]);
            
            $success = true;
            // Update session
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            
        } catch (PDOException $e) {
            $errors[] = "Failed to update profile.";
            error_log("Update profile error: " . $e->getMessage());
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($current_password)) {
        $errors[] = "Current password is required";
    }
    
    if (empty($new_password)) {
        $errors[] = "New password is required";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
        $errors[] = "Password must contain uppercase, lowercase, and numbers";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Verify current password
    if (empty($errors)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }
    }
    
    if (empty($errors)) {
        try {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            
            $success = true;
            
        } catch (PDOException $e) {
            $errors[] = "Failed to change password.";
            error_log("Change password error: " . $e->getMessage());
        }
    }
}

?>

<div style="margin-bottom: var(--spacing-2xl);">
    <h2>👤 My Profile</h2>
    <p style="color: var(--color-dark-gray);">Manage your account information and settings</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <strong>Success!</strong>
        <p>Your information has been updated successfully.</p>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Error:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="grid grid-3">
    <!-- Profile Summary -->
    <div class="card">
        <div class="card-header">
            <h3>👤 Account Summary</h3>
        </div>
        <div class="card-body">
            <div style="text-align: center; padding: var(--spacing-lg) 0;">
                <div style="
                    width: 80px;
                    height: 80px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #00d4ff, #0099cc);
                    margin: 0 auto var(--spacing-md);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 36px;
                ">
                    👤
                </div>
                <h4 style="margin: var(--spacing-sm) 0;">
                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                </h4>
                <p style="color: var(--color-dark-gray); margin: 0;">
                    <?php echo htmlspecialchars($user['email']); ?>
                </p>
            </div>
            
            <hr style="border: none; border-top: 1px solid #eee; margin: var(--spacing-lg) 0;">
            
            <div class="stat-item">
                <span class="stat-label">Username</span>
                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
            </div>
            
            <div class="stat-item">
                <span class="stat-label">Role</span>
                <span class="badge" style="
                    background-color: <?php echo $user['role'] === 'admin' ? '#fef3c7' : '#dbeafe'; ?>;
                    color: <?php echo $user['role'] === 'admin' ? '#78350f' : '#0c2d6b'; ?>;
                ">
                    <?php echo ucfirst($user['role']); ?>
                </span>
            </div>
            
            <div class="stat-item">
                <span class="stat-label">Member Since</span>
                <strong><?php echo date('M d, Y', strtotime($user['registration_date'])); ?></strong>
            </div>
        </div>
    </div>
    
    <!-- Profile Information -->
    <div style="grid-column: span 2;">
        <div class="card">
            <div class="card-header">
                <h3>✏️ Edit Profile</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input 
                                type="text" 
                                id="first_name" 
                                name="first_name" 
                                value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input 
                                type="text" 
                                id="last_name" 
                                name="last_name" 
                                value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($user['email']); ?>"
                            disabled
                            style="background-color: #f5f5f5; cursor: not-allowed;"
                        >
                        <small style="color: var(--color-dark-gray);">Email cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea 
                            id="bio" 
                            name="bio"
                            placeholder="Tell us about yourself..."
                        ><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="grid grid-3">
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input 
                                type="text" 
                                id="phone" 
                                name="phone" 
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                placeholder="+1 (555) 000-0000"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="city">City</label>
                            <input 
                                type="text" 
                                id="city" 
                                name="city" 
                                value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input 
                                type="text" 
                                id="country" 
                                name="country" 
                                value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>"
                            >
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Change Password -->
<div class="card" style="margin-top: var(--spacing-lg);">
    <div class="card-header">
        <h3>🔒 Change Password</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="profile.php">
            <input type="hidden" name="action" value="change_password">
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input 
                    type="password" 
                    id="current_password" 
                    name="current_password" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    placeholder="Min 8 characters"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-primary">Update Password</button>
        </form>
    </div>
</div>

<?php
// Include footer
require_once '../../private/includes/footer.php';
?>
