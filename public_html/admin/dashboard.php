<?php
/**
 * Admin Panel - Dashboard
 * 
 * Displays admin overview with:
 * - Pending approvals count
 * - User statistics
 * - System stats
 * - Quick actions
 */

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SESSION['status'] !== 'active') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Admin Dashboard";

// Include header
require_once '../../private/includes/header.php';

// Database connection
require_once '../../private/config/db_connect.php';

$admin_id = $_SESSION['user_id'];

// Fetch admin statistics
try {
    // User stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_approvals,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_users
        FROM users
        WHERE status != 'deleted'
    ");
    $stmt->execute();
    $user_stats = $stmt->fetch();
    
    // Grade stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_grades,
            AVG(percentage) as avg_percentage
        FROM grades
    ");
    $stmt->execute();
    $grade_stats = $stmt->fetch();
    
    // Task stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_tasks
        FROM tasks
    ");
    $stmt->execute();
    $task_stats = $stmt->fetch();
    
    // Blog stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_blogs,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_blogs,
            SUM(view_count) as total_views
        FROM blogs
    ");
    $stmt->execute();
    $blog_stats = $stmt->fetch();
    
    // Recent approvals
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.approval_date, a.first_name as admin_name
        FROM users u
        LEFT JOIN users a ON u.approved_by = a.id
        WHERE u.status = 'active' AND u.approval_date IS NOT NULL
        ORDER BY u.approval_date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_approvals = $stmt->fetchAll();
    
    // Pending users
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, registration_date
        FROM users
        WHERE status = 'pending'
        ORDER BY registration_date ASC
    ");
    $stmt->execute();
    $pending_users = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $user_stats = ['total_users' => 0, 'pending_approvals' => 0, 'active_users' => 0];
    $task_stats = ['total_tasks' => 0, 'completed_tasks' => 0, 'overdue_tasks' => 0];
    $blog_stats = ['total_blogs' => 0, 'published_blogs' => 0, 'total_views' => 0];
    $recent_approvals = [];
    $pending_users = [];
}

?>

<div style="margin-bottom: var(--spacing-2xl);">
    <h2>🔧 Admin Dashboard</h2>
    <p style="color: var(--color-dark-gray);">System overview and management controls</p>
</div>

<!-- Key Metrics -->
<div class="grid grid-4">
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Total Users</span>
                <span class="stat-value"><?php echo $user_stats['total_users']; ?></span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Pending Approval</span>
                <span class="stat-value" style="color: #f59e0b;">
                    <?php echo $user_stats['pending_approvals']; ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Active Users</span>
                <span class="stat-value" style="color: #10b981;">
                    <?php echo $user_stats['active_users']; ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Suspended</span>
                <span class="stat-value" style="color: #ef4444;">
                    <?php echo $user_stats['suspended_users']; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Academic Stats -->
<div class="grid grid-3">
    <div class="card">
        <div class="card-header">
            <h3>📊 Grades</h3>
        </div>
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Total Grades</span>
                <span class="stat-value"><?php echo $grade_stats['total_grades']; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Average Score</span>
                <span class="stat-value">
                    <?php echo $grade_stats['avg_percentage'] ? number_format($grade_stats['avg_percentage'], 1) . '%' : 'N/A'; ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>📋 Tasks</h3>
        </div>
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Total Tasks</span>
                <span class="stat-value"><?php echo $task_stats['total_tasks']; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Completed</span>
                <span class="stat-value" style="color: #10b981;">
                    <?php echo $task_stats['completed_tasks']; ?>
                </span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Overdue</span>
                <span class="stat-value" style="color: #ef4444;">
                    <?php echo $task_stats['overdue_tasks']; ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>📝 Blog</h3>
        </div>
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Total Posts</span>
                <span class="stat-value"><?php echo $blog_stats['total_blogs']; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Published</span>
                <span class="stat-value" style="color: #10b981;">
                    <?php echo $blog_stats['published_blogs']; ?>
                </span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Views</span>
                <span class="stat-value"><?php echo $blog_stats['total_views']; ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Pending Approvals Alert -->
<?php if ($user_stats['pending_approvals'] > 0): ?>
    <div class="alert alert-warning" style="margin-bottom: var(--spacing-lg);">
        <strong>⏳ Pending User Approvals</strong>
        <p>You have <?php echo $user_stats['pending_approvals']; ?> user(s) waiting for approval.</p>
        <a href="users.php?status=pending" class="btn btn-warning btn-small">
            Review Pending Users →
        </a>
    </div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="grid grid-3">
    <div class="card">
        <div class="card-header">
            <h3>⚡ Quick Actions</h3>
        </div>
        <div class="card-body">
            <a href="users.php?status=pending" class="btn btn-primary btn-small btn-block" style="margin-bottom: var(--spacing-sm);">
                👥 Pending Approvals
            </a>
            <a href="users.php" class="btn btn-secondary btn-small btn-block" style="margin-bottom: var(--spacing-sm);">
                👤 Manage Users
            </a>
            <a href="audit.php" class="btn btn-secondary btn-small btn-block">
                📋 View Audit Log
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>📊 Analytics</h3>
        </div>
        <div class="card-body">
            <p style="font-size: var(--font-size-small); margin-bottom: var(--spacing-md);">
                View detailed analytics and reports
            </p>
            <a href="analytics.php" class="btn btn-secondary btn-small btn-block">
                📈 View Analytics
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>⚙️ Settings</h3>
        </div>
        <div class="card-body">
            <p style="font-size: var(--font-size-small); margin-bottom: var(--spacing-md);">
                System settings and configuration
            </p>
            <a href="settings.php" class="btn btn-secondary btn-small btn-block">
                ⚙️ Settings
            </a>
        </div>
    </div>
</div>

<!-- Pending Users Table -->
<?php if (!empty($pending_users)): ?>
    <div class="card">
        <div class="card-header">
            <h3>⏳ Pending Approvals (<?php echo count($pending_users); ?>)</h3>
        </div>
        <div class="card-body">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_users as $user): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </td>
                                <td>
                                    <small><?php echo date('M d, Y', strtotime($user['registration_date'])); ?></small>
                                </td>
                                <td>
                                    <form method="POST" action="users.php?status=pending" style="display: inline;">
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-small">
                                            ✓ Approve
                                        </button>
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
// Include footer
require_once '../../private/includes/footer.php';
?>
