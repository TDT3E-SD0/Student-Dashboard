<?php
/**
 * Student Tasks Management Page
 * 
 * Features:
 * - Create, read, update, delete tasks
 * - Task filtering by status and priority
 * - Deadline tracking
 * - Task statistics
 */

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['status'] !== 'active') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Tasks";

// Include header
require_once '../../private/includes/header.php';

// Database connection
require_once '../../private/config/db_connect.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;
$filter_status = $_GET['status'] ?? 'all';

// Handle create/update task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $deadline = trim($_POST['deadline'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? 'not-started';
    
    // Validation
    if (empty($title)) {
        $errors[] = "Task title is required";
    } elseif (strlen($title) > 255) {
        $errors[] = "Title must not exceed 255 characters";
    }
    
    if (empty($deadline)) {
        $errors[] = "Deadline is required";
    } elseif (!strtotime($deadline)) {
        $errors[] = "Invalid deadline format";
    }
    
    if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
        $errors[] = "Invalid priority level";
    }
    
    if (!in_array($status, ['not-started', 'in-progress', 'completed', 'overdue', 'cancelled'])) {
        $errors[] = "Invalid status";
    }
    
    // Insert task if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tasks (
                    user_id, title, description, subject, priority,
                    deadline, category, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $title,
                $description ?: null,
                $subject ?: null,
                $priority,
                $deadline,
                $category ?: null,
                $status
            ]);
            
            $success = true;
            
        } catch (PDOException $e) {
            $errors[] = "Failed to create task. Please try again.";
            error_log("Create task error: " . $e->getMessage());
        }
    }
}

// Fetch tasks
try {
    $query = "
        SELECT * FROM tasks
        WHERE user_id = ?
    ";
    $params = [$user_id];
    
    if ($filter_status !== 'all') {
        $query .= " AND status = ?";
        $params[] = $filter_status;
    }
    
    $query .= " ORDER BY deadline ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $all_tasks = $stmt->fetchAll();
    
    // Get task statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'not-started' THEN 1 ELSE 0 END) as not_started,
            SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue
        FROM tasks
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $task_stats = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Fetch tasks error: " . $e->getMessage());
    $all_tasks = [];
    $task_stats = ['total' => 0, 'completed' => 0, 'in_progress' => 0, 'not_started' => 0, 'overdue' => 0];
}

?>

<div style="margin-bottom: var(--spacing-2xl);">
    <h2>📋 Tasks & Assignments</h2>
    <p style="color: var(--color-dark-gray);">Manage your academic tasks and deadlines</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <strong>Success!</strong>
        <p>Your task has been created successfully.</p>
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

<!-- Task Statistics -->
<div class="grid grid-4">
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Total Tasks</span>
                <span class="stat-value"><?php echo $task_stats['total']; ?></span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Completed</span>
                <span class="stat-value" style="color: #10b981;"><?php echo $task_stats['completed']; ?></span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">In Progress</span>
                <span class="stat-value" style="color: #3b82f6;"><?php echo $task_stats['in_progress']; ?></span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Overdue</span>
                <span class="stat-value" style="color: #ef4444;"><?php echo $task_stats['overdue']; ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Create Task Form -->
<div class="card" style="margin-bottom: var(--spacing-lg);">
    <div class="card-header">
        <h3>➕ Create New Task</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="tasks.php">
            <input type="hidden" name="action" value="create">
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label for="title">Task Title</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        placeholder="e.g., Math Assignment Chapter 5"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input 
                        type="text" 
                        id="subject" 
                        name="subject" 
                        placeholder="e.g., Mathematics"
                    >
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description (Optional)</label>
                <textarea 
                    id="description" 
                    name="description"
                    placeholder="Add more details about this task..."
                ></textarea>
            </div>
            
            <div class="grid grid-3">
                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="deadline">Deadline</label>
                    <input 
                        type="datetime-local" 
                        id="deadline" 
                        name="deadline"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="category">Category (Optional)</label>
                    <select id="category" name="category">
                        <option value="">-- Select Category --</option>
                        <option value="homework">Homework</option>
                        <option value="project">Project</option>
                        <option value="reading">Reading</option>
                        <option value="exam-prep">Exam Prep</option>
                        <option value="research">Research</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Create Task</button>
        </form>
    </div>
</div>

<!-- Filter Buttons -->
<div style="margin-bottom: var(--spacing-lg); display: flex; gap: var(--spacing-md); flex-wrap: wrap;">
    <a href="tasks.php?status=all" class="btn <?php echo $filter_status === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
        All Tasks
    </a>
    <a href="tasks.php?status=not-started" class="btn <?php echo $filter_status === 'not-started' ? 'btn-primary' : 'btn-secondary'; ?>">
        Not Started
    </a>
    <a href="tasks.php?status=in-progress" class="btn <?php echo $filter_status === 'in-progress' ? 'btn-primary' : 'btn-secondary'; ?>">
        In Progress
    </a>
    <a href="tasks.php?status=completed" class="btn <?php echo $filter_status === 'completed' ? 'btn-primary' : 'btn-secondary'; ?>">
        Completed
    </a>
    <a href="tasks.php?status=overdue" class="btn <?php echo $filter_status === 'overdue' ? 'btn-primary' : 'btn-secondary'; ?>">
        Overdue
    </a>
</div>

<!-- Tasks List -->
<div class="card">
    <div class="card-header">
        <h3>📋 Your Tasks</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($all_tasks)): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Priority</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_tasks as $task): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($task['subject'] ?? '-'); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $priority_colors = [
                                        'low' => '#3b82f6',
                                        'medium' => '#f59e0b',
                                        'high' => '#ef4444',
                                        'urgent' => '#7c2d12'
                                    ];
                                    ?>
                                    <span class="badge" style="
                                        background-color: <?php echo $priority_colors[$task['priority']]; ?>33;
                                        color: <?php echo $priority_colors[$task['priority']]; ?>;
                                        border: 1px solid <?php echo $priority_colors[$task['priority']]; ?>;
                                    ">
                                        <?php echo ucfirst($task['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('M d, Y H:i', strtotime($task['deadline'])); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $status_colors = [
                                        'not-started' => '#6b7280',
                                        'in-progress' => '#3b82f6',
                                        'completed' => '#10b981',
                                        'overdue' => '#ef4444',
                                        'cancelled' => '#9ca3af'
                                    ];
                                    ?>
                                    <span class="badge" style="
                                        background-color: <?php echo $status_colors[$task['status']]; ?>33;
                                        color: <?php echo $status_colors[$task['status']]; ?>;
                                        border: 1px solid <?php echo $status_colors[$task['status']]; ?>;
                                    ">
                                        <?php echo ucfirst(str_replace('-', ' ', $task['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($task['category'] ?? '-'); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--color-dark-gray); padding: var(--spacing-2xl) 0;">
                ✅ No tasks yet. You're all caught up!
            </p>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
require_once '../../private/includes/footer.php';
?>
