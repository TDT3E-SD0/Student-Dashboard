<?php
/**
 * Student Dashboard Home Page
 * 
 * Displays:
 * - Greeting with user name
 * - Upcoming Tasks widget
 * - GPA Summary widget
 * - Recent Blog Posts widget
 * - Quick Access to Google Drive
 * - At-a-glance statistics
 */

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['status'] !== 'active') {
    header("Location: login.php");
    exit;
}

$page_title = "Dashboard";

// Include header
require_once '../private/includes/header.php';

// Database connection
require_once '../private/config/db_connect.php';

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? '';
$username = $_SESSION['username'] ?? '';

// Fetch user stats and data
try {
    // Get upcoming tasks
    $stmt_tasks = $pdo->prepare("
        SELECT id, title, deadline, priority, status
        FROM tasks
        WHERE user_id = ? AND status IN ('not-started', 'in-progress')
        ORDER BY deadline ASC
        LIMIT 5
    ");
    $stmt_tasks->execute([$user_id]);
    $upcoming_tasks = $stmt_tasks->fetchAll();
    
    // Get grades statistics
    $stmt_grades = $pdo->prepare("
        SELECT AVG(percentage) as avg_percentage, COUNT(*) as total_grades
        FROM grades
        WHERE user_id = ?
    ");
    $stmt_grades->execute([$user_id]);
    $grade_stats = $stmt_grades->fetch();
    
    // Get recent blog posts
    $stmt_blogs = $pdo->prepare("
        SELECT id, title, published_date, view_count
        FROM blogs
        WHERE author_id = ? AND status = 'published'
        ORDER BY published_date DESC
        LIMIT 4
    ");
    $stmt_blogs->execute([$user_id]);
    $recent_blogs = $stmt_blogs->fetchAll();
    
    // Get task statistics
    $stmt_task_stats = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'in-progress' THEN 1 END) as in_progress,
            COUNT(CASE WHEN status = 'not-started' THEN 1 END) as not_started,
            COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue
        FROM tasks
        WHERE user_id = ?
    ");
    $stmt_task_stats->execute([$user_id]);
    $task_stats = $stmt_task_stats->fetch();
    
    // Calculate GPA (assuming 4.0 scale)
    $avg_percentage = $grade_stats['avg_percentage'] ?? 0;
    $gpa = ($avg_percentage / 100) * 4.0;
    $gpa = min($gpa, 4.0); // Cap at 4.0
    
} catch (PDOException $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    $upcoming_tasks = [];
    $recent_blogs = [];
    $task_stats = ['completed' => 0, 'in_progress' => 0, 'not_started' => 0, 'overdue' => 0];
    $gpa = 0;
}

?>

<section class="dashboard-hero">
    <h1>Welcome back, <?php echo htmlspecialchars($first_name ?: $username); ?>! 👋</h1>
    <p>Here's your academic overview for today</p>
</section>

<!-- Quick Stats Cards -->
<div class="grid grid-4">
    <div class="widget">
        <div class="stat-item">
            <span class="stat-label">Tasks Completed</span>
            <span class="stat-value"><?php echo $task_stats['completed']; ?></span>
        </div>
    </div>
    
    <div class="widget">
        <div class="stat-item">
            <span class="stat-label">In Progress</span>
            <span class="stat-value"><?php echo $task_stats['in_progress']; ?></span>
        </div>
    </div>
    
    <div class="widget">
        <div class="stat-item">
            <span class="stat-label">Total Tasks</span>
            <span class="stat-value"><?php echo $task_stats['completed'] + $task_stats['in_progress'] + $task_stats['not_started'] + $task_stats['overdue']; ?></span>
        </div>
    </div>
    
    <div class="widget">
        <div class="stat-item">
            <span class="stat-label">Current GPA</span>
            <span class="stat-value"><?php echo number_format($gpa, 2); ?></span>
        </div>
    </div>
</div>

<!-- Main Dashboard Widgets -->
<div class="grid grid-2">
    
    <!-- Upcoming Tasks Widget -->
    <div class="card">
        <div class="card-header">
            <h3>📋 Upcoming Tasks</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($upcoming_tasks)): ?>
                <div class="task-list">
                    <?php foreach ($upcoming_tasks as $task): ?>
                        <div class="stat-item">
                            <div>
                                <p style="margin: 0; font-weight: 500;">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </p>
                                <small style="color: var(--color-dark-gray);">
                                    Due: <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                                </small>
                            </div>
                            <span class="badge badge-<?php 
                                echo $task['priority'] === 'high' || $task['priority'] === 'urgent' ? 'danger' : 
                                     ($task['priority'] === 'medium' ? 'warning' : 'info');
                            ?>">
                                <?php echo ucfirst($task['priority']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: var(--spacing-md);">
                    <a href="pages/tasks.php" class="btn btn-secondary btn-small">View All Tasks →</a>
                </div>
            <?php else: ?>
                <p style="color: var(--color-dark-gray); text-align: center; padding: var(--spacing-lg) 0;">
                    No upcoming tasks. Great job! 🎉
                </p>
                <a href="pages/tasks.php" class="btn btn-primary btn-small btn-block">Create New Task</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- GPA Summary Widget -->
    <div class="card">
        <div class="card-header">
            <h3>📊 Academic Performance</h3>
        </div>
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Current GPA</span>
                <span class="stat-value"><?php echo number_format($gpa, 2); ?>/4.0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Average Grade</span>
                <span class="stat-value"><?php echo number_format($avg_percentage, 1); ?>%</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Assessments</span>
                <span class="stat-value"><?php echo $grade_stats['total_grades']; ?></span>
            </div>
            
            <!-- Performance Bar -->
            <div style="margin-top: var(--spacing-lg);">
                <label style="margin-bottom: var(--spacing-sm);">Performance Trend</label>
                <div style="background-color: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden;">
                    <div style="
                        background: linear-gradient(90deg, var(--color-neon-blue), #00d4ff);
                        height: 100%;
                        width: <?php echo min($avg_percentage, 100); ?>%;
                        transition: width 0.3s ease;
                    "></div>
                </div>
            </div>
            
            <div style="margin-top: var(--spacing-md);">
                <a href="pages/grades.php" class="btn btn-secondary btn-small">View Grades →</a>
            </div>
        </div>
    </div>
    
</div>

<!-- Recent Blog Posts Widget -->
<div class="card">
    <div class="card-header">
        <h3>📝 Recent Blog Posts</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($recent_blogs)): ?>
            <div class="blog-list">
                <?php foreach ($recent_blogs as $blog): ?>
                    <div class="stat-item">
                        <div>
                            <p style="margin: 0; font-weight: 500;">
                                <?php echo htmlspecialchars($blog['title']); ?>
                            </p>
                            <small style="color: var(--color-dark-gray);">
                                <?php echo date('M d, Y', strtotime($blog['published_date'])); ?> 
                                • <?php echo $blog['view_count']; ?> views
                            </small>
                        </div>
                        <a href="pages/blog.php?id=<?php echo $blog['id']; ?>" class="btn btn-small btn-secondary">Read →</a>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: var(--spacing-md);">
                <a href="pages/blog.php" class="btn btn-secondary btn-small">View All Posts →</a>
            </div>
        <?php else: ?>
            <p style="color: var(--color-dark-gray); text-align: center; padding: var(--spacing-lg) 0;">
                No published posts yet. Start sharing your thoughts! 📖
            </p>
            <a href="pages/blog.php?action=create" class="btn btn-primary btn-small btn-block">Write First Post</a>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-3">
    <div class="card">
        <div class="card-header">
            <h3>⚡ Quick Actions</h3>
        </div>
        <div class="card-body">
            <a href="pages/tasks.php?action=create" class="btn btn-primary btn-small btn-block" style="margin-bottom: var(--spacing-sm);">
                ➕ New Task
            </a>
            <a href="pages/grades.php" class="btn btn-secondary btn-small btn-block" style="margin-bottom: var(--spacing-sm);">
                📊 View Grades
            </a>
            <a href="pages/profile.php" class="btn btn-secondary btn-small btn-block">
                👤 Edit Profile
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>📁 Files & Storage</h3>
        </div>
        <div class="card-body">
            <p style="font-size: var(--font-size-small); margin-bottom: var(--spacing-md);">
                Manage your files and access Google Drive integration
            </p>
            <a href="pages/files.php" class="btn btn-primary btn-small btn-block" style="margin-bottom: var(--spacing-sm);">
                🔗 Open Files
            </a>
            <a href="https://drive.google.com" target="_blank" class="btn btn-secondary btn-small btn-block">
                🚀 Google Drive
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>ℹ️ Help & Support</h3>
        </div>
        <div class="card-body">
            <p style="font-size: var(--font-size-small); margin-bottom: var(--spacing-md);">
                Need help? Check out our documentation or contact support
            </p>
            <a href="#" class="btn btn-secondary btn-small btn-block" style="margin-bottom: var(--spacing-sm);">
                📚 Documentation
            </a>
            <a href="#" class="btn btn-secondary btn-small btn-block">
                💬 Contact Support
            </a>
        </div>
    </div>
</div>

<style>
.dashboard-hero {
    text-align: center;
    margin-bottom: var(--spacing-2xl);
    padding: var(--spacing-2xl);
    background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(0, 153, 204, 0.1));
    border-radius: var(--border-radius-lg);
    border-left: 4px solid var(--color-neon-blue);
}

.dashboard-hero h1 {
    margin-bottom: var(--spacing-sm);
    color: var(--color-deep-blue);
}

.dashboard-hero p {
    font-size: var(--font-size-large);
    color: var(--color-dark-gray);
    margin: 0;
}

.task-list,
.blog-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}
</style>

<?php
// Include footer
require_once '../private/includes/footer.php';
?>
