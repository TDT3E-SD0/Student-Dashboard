<?php
/**
 * Student Grades Management Page
 * 
 * Allows students to:
 * - View all their grades
 * - Input new grades (subject, type, score)
 * - See GPA calculation
 * - Get visual alerts for low grades
 */

session_start();

// Redirect if not logged in or not a student
if (!isset($_SESSION['user_id']) || $_SESSION['status'] !== 'active') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Grades";

// Include header
require_once '../../private/includes/header.php';

// Database connection
require_once '../../private/config/db_connect.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

/**
 * Calculate GPA from grades
 * Assumes 10-point scale, converted to 4.0 scale
 * 10 = A (4.0)
 * 9 = A- (3.7)
 * 8 = B (3.0)
 * 7 = B- (2.7)
 * 6 = C (2.0)
 * 5 = D (1.0)
 * <5 = F (0.0)
 */
function calculateGPA($scores) {
    if (empty($scores)) {
        return 0;
    }
    
    $grade_scale = [
        'A' => 4.0,
        'B' => 3.0,
        'C' => 2.0,
        'D' => 1.0,
        'F' => 0.0,
    ];
    
    $total_points = 0;
    $total_weight = 0;
    
    foreach ($scores as $grade) {
        $percentage = $grade['percentage'];
        $weight = $grade['weight'] ?? 1.0;
        
        // Convert percentage to letter grade
        if ($percentage >= 90) {
            $letter = 'A';
        } elseif ($percentage >= 80) {
            $letter = 'B';
        } elseif ($percentage >= 70) {
            $letter = 'C';
        } elseif ($percentage >= 60) {
            $letter = 'D';
        } else {
            $letter = 'F';
        }
        
        $total_points += $grade_scale[$letter] * $weight;
        $total_weight += $weight;
    }
    
    return $total_weight > 0 ? $total_points / $total_weight : 0;
}

/**
 * Get letter grade from percentage
 */
function getLetterGrade($percentage) {
    if ($percentage >= 90) return 'A';
    if ($percentage >= 80) return 'B';
    if ($percentage >= 70) return 'C';
    if ($percentage >= 60) return 'D';
    return 'F';
}

/**
 * Get grade color based on score
 */
function getGradeColor($percentage) {
    if ($percentage >= 80) return '#10b981'; // Green
    if ($percentage >= 70) return '#3b82f6'; // Blue
    if ($percentage >= 60) return '#f59e0b'; // Orange
    return '#ef4444'; // Red
}

// Handle form submission (Add Grade)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_grade') {
    $subject = trim($_POST['subject'] ?? '');
    $assessment_type = trim($_POST['assessment_type'] ?? '');
    $score = trim($_POST['score'] ?? '');
    $max_score = trim($_POST['max_score'] ?? '100');
    $weight = trim($_POST['weight'] ?? '1.0');
    $assessment_date = trim($_POST['assessment_date'] ?? '');
    $feedback = trim($_POST['feedback'] ?? '');
    $instructor_name = trim($_POST['instructor_name'] ?? '');
    
    // Validation
    if (empty($subject)) {
        $errors[] = "Subject is required";
    } elseif (strlen($subject) > 100) {
        $errors[] = "Subject must not exceed 100 characters";
    }
    
    if (empty($assessment_type)) {
        $errors[] = "Assessment type is required";
    }
    
    if (empty($score)) {
        $errors[] = "Score is required";
    } elseif (!is_numeric($score)) {
        $errors[] = "Score must be a number";
    } elseif ($score < 0) {
        $errors[] = "Score must be positive";
    }
    
    if (empty($max_score)) {
        $errors[] = "Max score is required";
    } elseif (!is_numeric($max_score) || $max_score <= 0) {
        $errors[] = "Max score must be a positive number";
    }
    
    if (!is_numeric($weight) || $weight <= 0) {
        $errors[] = "Weight must be a positive number";
    }
    
    if (empty($assessment_date)) {
        $errors[] = "Assessment date is required";
    } elseif (!strtotime($assessment_date)) {
        $errors[] = "Invalid assessment date";
    }
    
    // Insert grade if no errors
    if (empty($errors)) {
        try {
            $percentage = ($score / $max_score) * 100;
            $letter_grade = getLetterGrade($percentage);
            
            $stmt = $pdo->prepare("
                INSERT INTO grades (
                    user_id, subject, assessment_type, score, max_score,
                    weight, grade_letter, assessment_date, feedback, instructor_name
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $subject,
                $assessment_type,
                $score,
                $max_score,
                $weight,
                $letter_grade,
                $assessment_date,
                $feedback ?: null,
                $instructor_name ?: null
            ]);
            
            $success = true;
            
        } catch (PDOException $e) {
            $errors[] = "Failed to add grade. Please try again.";
            error_log("Add grade error: " . $e->getMessage());
        }
    }
}

// Fetch all grades for the user
try {
    $stmt = $pdo->prepare("
        SELECT * FROM grades
        WHERE user_id = ?
        ORDER BY assessment_date DESC
    ");
    $stmt->execute([$user_id]);
    $all_grades = $stmt->fetchAll();
    
    // Calculate GPA
    $gpa = calculateGPA($all_grades);
    
} catch (PDOException $e) {
    error_log("Fetch grades error: " . $e->getMessage());
    $all_grades = [];
    $gpa = 0;
}

?>

<div style="margin-bottom: var(--spacing-2xl);">
    <h2>📊 Your Grades</h2>
    <p style="color: var(--color-dark-gray);">Track your academic performance and manage your assessments</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <strong>Grade Added Successfully!</strong>
        <p>Your new grade has been recorded and your GPA has been updated.</p>
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

<!-- GPA Summary Card -->
<div class="grid grid-3">
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Current GPA</span>
                <span class="stat-value"><?php echo number_format($gpa, 2); ?>/4.0</span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Total Assessments</span>
                <span class="stat-value"><?php echo count($all_grades); ?></span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Average Score</span>
                <span class="stat-value">
                    <?php 
                    if (!empty($all_grades)) {
                        $avg = array_sum(array_column($all_grades, 'percentage')) / count($all_grades);
                        echo number_format($avg, 1) . '%';
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Add New Grade Form -->
<div class="card" style="margin-bottom: var(--spacing-lg);">
    <div class="card-header">
        <h3>➕ Add New Grade</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="grades.php">
            <input type="hidden" name="action" value="add_grade">
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input 
                        type="text" 
                        id="subject" 
                        name="subject" 
                        placeholder="e.g., Mathematics, Physics"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="assessment_type">Assessment Type</label>
                    <select id="assessment_type" name="assessment_type" required>
                        <option value="">-- Select Type --</option>
                        <option value="quiz">Quiz</option>
                        <option value="assignment">Assignment</option>
                        <option value="mid-term">Mid-term</option>
                        <option value="final">Final</option>
                        <option value="project">Project</option>
                        <option value="participation">Participation</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-3">
                <div class="form-group">
                    <label for="score">Score</label>
                    <input 
                        type="number" 
                        id="score" 
                        name="score" 
                        step="0.01"
                        placeholder="e.g., 85"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="max_score">Max Score</label>
                    <input 
                        type="number" 
                        id="max_score" 
                        name="max_score" 
                        step="0.01"
                        value="100"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="weight">Weight</label>
                    <input 
                        type="number" 
                        id="weight" 
                        name="weight" 
                        step="0.1"
                        value="1.0"
                        placeholder="e.g., 1.0"
                        required
                    >
                </div>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label for="assessment_date">Assessment Date</label>
                    <input 
                        type="date" 
                        id="assessment_date" 
                        name="assessment_date"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="instructor_name">Instructor Name (Optional)</label>
                    <input 
                        type="text" 
                        id="instructor_name" 
                        name="instructor_name" 
                        placeholder="e.g., Dr. Smith"
                    >
                </div>
            </div>
            
            <div class="form-group">
                <label for="feedback">Feedback (Optional)</label>
                <textarea 
                    id="feedback" 
                    name="feedback"
                    placeholder="Add any notes or feedback about this grade..."
                ></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Add Grade</button>
        </form>
    </div>
</div>

<!-- Grades Table -->
<div class="card">
    <div class="card-header">
        <h3>📋 All Grades</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($all_grades)): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                            <th>Weight</th>
                            <th>Date</th>
                            <th>Instructor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_grades as $grade): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($grade['subject']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo ucfirst(str_replace('-', ' ', $grade['assessment_type'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $grade['score'] . ' / ' . $grade['max_score']; ?>
                                </td>
                                <td>
                                    <strong style="color: <?php echo getGradeColor($grade['percentage']); ?>;">
                                        <?php echo number_format($grade['percentage'], 1); ?>%
                                    </strong>
                                </td>
                                <td>
                                    <?php
                                    $bg_color = getGradeColor($grade['percentage']);
                                    $letter = $grade['grade_letter'];
                                    $is_low = $letter === 'F';
                                    ?>
                                    <span class="badge" style="
                                        background-color: <?php echo $bg_color; ?>33;
                                        color: <?php echo $bg_color; ?>;
                                        border: 1px solid <?php echo $bg_color; ?>;
                                        font-weight: 700;
                                        padding: 6px 12px;
                                        <?php echo $is_low ? 'box-shadow: 0 0 10px rgba(239, 68, 68, 0.3);' : ''; ?>
                                    ">
                                        <?php echo $letter; ?>
                                        <?php echo $is_low ? ' ⚠️' : ''; ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo $grade['weight']; ?></small>
                                </td>
                                <td>
                                    <small><?php echo date('M d, Y', strtotime($grade['assessment_date'])); ?></small>
                                </td>
                                <td>
                                    <small>
                                        <?php echo htmlspecialchars($grade['instructor_name'] ?? '-'); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--color-dark-gray); padding: var(--spacing-2xl) 0;">
                📚 No grades recorded yet. Add your first grade above!
            </p>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Low grade alert styling */
    tr td:has(+ td .badge[style*="ef4444"]) {
        background-color: #fee2e2;
    }
    
    .form-group input[type="number"],
    .form-group select {
        transition: var(--transition);
    }
</style>

<?php
// Include footer
require_once '../../private/includes/footer.php';
?>
