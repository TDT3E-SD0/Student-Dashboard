<?php
/**
 * Admin Panel - User Management
 * 
 * Features:
 * - List all registered users
 * - Show registration date and status
 * - Approve pending accounts
 * - View user details
 * - Suspend/Delete users (admin only)
 */

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SESSION['status'] !== 'active') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Admin - User Management";

// Include header
require_once '../../private/includes/header.php';

// Database connection
require_once '../../private/config/db_connect.php';

$admin_id = $_SESSION['user_id'];
$errors = [];
$success = false;
$filter_status = $_GET['status'] ?? 'all';

// Handle user approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $target_user_id = intval($_POST['user_id'] ?? 0);
    
    if ($target_user_id <= 0) {
        $errors[] = "Invalid user ID";
    } else {
        try {
            if ($action === 'approve') {
                // Approve user (pending -> active)
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET status = 'active', approval_date = NOW(), approved_by = ?
                    WHERE id = ? AND status = 'pending'
                ");
                
                if ($stmt->execute([$admin_id, $target_user_id])) {
                    if ($stmt->rowCount() > 0) {
                        $success = true;
                        // Log audit trail
                        logAuditTrail($pdo, $admin_id, 'user_approved', $target_user_id, 'users', $target_user_id, null, ['status' => 'active']);
                    } else {
                        $errors[] = "User not found or already approved";
                    }
                }
            } elseif ($action === 'suspend') {
                // Suspend user
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET status = 'suspended'
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$target_user_id])) {
                    $success = true;
                    logAuditTrail($pdo, $admin_id, 'user_suspended', $target_user_id, 'users', $target_user_id, null, ['status' => 'suspended']);
                }
            } elseif ($action === 'delete') {
                // Soft delete user
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET status = 'deleted'
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$target_user_id])) {
                    $success = true;
                    logAuditTrail($pdo, $admin_id, 'user_deleted', $target_user_id, 'users', $target_user_id, null, ['status' => 'deleted']);
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
            error_log("Admin action error: " . $e->getMessage());
        }
    }
}

/**
 * Log admin actions for audit trail
 */
function logAuditTrail($pdo, $admin_id, $action, $target_user_id, $table, $record_id, $old_value = null, $new_value = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_log (admin_id, action, target_user_id, target_table, target_record_id, old_value, new_value, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $admin_id,
            $action,
            $target_user_id,
            $table,
            $record_id,
            json_encode($old_value),
            json_encode($new_value),
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

// Fetch users based on filter
try {
    $query = "SELECT * FROM users WHERE status != 'deleted'";
    $params = [];
    
    if ($filter_status !== 'all') {
        $query .= " AND status = ?";
        $params[] = $filter_status;
    }
    
    $query .= " ORDER BY registration_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $all_users = $stmt->fetchAll();
    
    // Get user statistics
    $stmt_stats = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended
        FROM users
        WHERE status != 'deleted'
    ");
    $stmt_stats->execute();
    $stats = $stmt_stats->fetch();
    
} catch (PDOException $e) {
    error_log("Fetch users error: " . $e->getMessage());
    $all_users = [];
    $stats = ['total' => 0, 'pending' => 0, 'active' => 0, 'suspended' => 0];
}

?>

<div style="margin-bottom: var(--spacing-2xl);">
    <h2>👥 User Management</h2>
    <p style="color: var(--color-dark-gray);">Manage registered users and approve pending accounts</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <strong>Action Completed Successfully!</strong>
        <p>The user status has been updated. Refreshing page...</p>
    </div>
    <script>
        setTimeout(function() {
            location.reload();
        }, 2000);
    </script>
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

<!-- Statistics Cards -->
<div class="grid grid-4">
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Total Users</span>
                <span class="stat-value"><?php echo $stats['total']; ?></span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Pending Approval</span>
                <span class="stat-value" style="color: #f59e0b;">
                    <?php echo $stats['pending']; ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Active Users</span>
                <span class="stat-value" style="color: #10b981;">
                    <?php echo $stats['active']; ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Suspended</span>
                <span class="stat-value" style="color: #ef4444;">
                    <?php echo $stats['suspended']; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Filter Buttons -->
<div style="margin-bottom: var(--spacing-lg); display: flex; gap: var(--spacing-md); flex-wrap: wrap;">
    <a href="users.php?status=all" class="btn <?php echo $filter_status === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
        All Users
    </a>
    <a href="users.php?status=pending" class="btn <?php echo $filter_status === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">
        Pending (<?php echo $stats['pending']; ?>)
    </a>
    <a href="users.php?status=active" class="btn <?php echo $filter_status === 'active' ? 'btn-primary' : 'btn-secondary'; ?>">
        Active (<?php echo $stats['active']; ?>)
    </a>
    <a href="users.php?status=suspended" class="btn <?php echo $filter_status === 'suspended' ? 'btn-primary' : 'btn-secondary'; ?>">
        Suspended (<?php echo $stats['suspended']; ?>)
    </a>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h3>📋 Registered Users</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($all_users)): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Approved</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </a>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($user['username']); ?></code>
                                </td>
                                <td>
                                    <span class="badge" style="
                                        background-color: <?php echo $user['role'] === 'admin' ? '#fef3c7' : '#dbeafe'; ?>;
                                        color: <?php echo $user['role'] === 'admin' ? '#78350f' : '#0c2d6b'; ?>;
                                    ">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status_color = match($user['status']) {
                                        'pending' => '#f59e0b',
                                        'active' => '#10b981',
                                        'suspended' => '#ef4444',
                                        default => '#6b7280'
                                    };
                                    $status_label = match($user['status']) {
                                        'pending' => 'Pending ⏳',
                                        'active' => 'Active ✓',
                                        'suspended' => 'Suspended 🔒',
                                        default => ucfirst($user['status'])
                                    };
                                    ?>
                                    <span class="badge" style="
                                        background-color: <?php echo $status_color; ?>33;
                                        color: <?php echo $status_color; ?>;
                                        border: 1px solid <?php echo $status_color; ?>;
                                    ">
                                        <?php echo $status_label; ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('M d, Y', strtotime($user['registration_date'])); ?></small>
                                </td>
                                <td>
                                    <small>
                                        <?php 
                                        if ($user['approval_date']) {
                                            echo date('M d, Y', strtotime($user['approval_date']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <form method="POST" action="users.php?status=<?php echo $filter_status; ?>" style="display: inline;">
                                        <?php if ($user['status'] === 'pending'): ?>
                                            <button type="submit" name="action" value="approve" class="btn btn-success btn-small" onclick="return confirm('Approve this user?');">
                                                ✓ Approve
                                            </button>
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <?php elseif ($user['status'] === 'active'): ?>
                                            <button type="submit" name="action" value="suspend" class="btn btn-warning btn-small" onclick="return confirm('Suspend this user?');">
                                                🔒 Suspend
                                            </button>
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <?php elseif ($user['status'] === 'suspended'): ?>
                                            <button type="submit" name="action" value="approve" class="btn btn-success btn-small" onclick="return confirm('Reactivate this user?');">
                                                ✓ Reactivate
                                            </button>
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <?php endif; ?>
                                        
                                        <button type="submit" name="action" value="delete" class="btn btn-danger btn-small" onclick="return confirm('Delete this user? This cannot be undone!');">
                                            🗑️ Delete
                                        </button>
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--color-dark-gray); padding: var(--spacing-2xl) 0;">
                No users found.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
require_once '../../private/includes/footer.php';
?>
