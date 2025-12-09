<?php
/**
 * Student Blog Page
 * 
 * Features:
 * - View all published blog posts
 * - Create new blog posts (markdown/rich text)
 * - Edit own posts
 * - Delete own posts
 * - View individual post with comments
 * - Like and comment functionality
 */

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['status'] !== 'active') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Blog";

// Include header
require_once '../../private/includes/header.php';

// Database connection
require_once '../../private/config/db_connect.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;
$action = $_GET['action'] ?? 'list';
$post_id = intval($_GET['id'] ?? 0);

/**
 * Generate URL-friendly slug
 */
function generateSlug($title) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// Handle Create Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    
    // Validation
    if (empty($title)) {
        $errors[] = "Title is required";
    } elseif (strlen($title) > 255) {
        $errors[] = "Title must not exceed 255 characters";
    }
    
    if (empty($content)) {
        $errors[] = "Content is required";
    } elseif (strlen($content) < 50) {
        $errors[] = "Content must be at least 50 characters";
    }
    
    if (strlen($excerpt) > 500) {
        $errors[] = "Excerpt must not exceed 500 characters";
    }
    
    if (!in_array($status, ['draft', 'published', 'archived'])) {
        $errors[] = "Invalid status";
    }
    
    // Insert post if no errors
    if (empty($errors)) {
        try {
            $slug = generateSlug($title);
            $published_date = $status === 'published' ? date('Y-m-d H:i:s') : null;
            
            $stmt = $pdo->prepare("
                INSERT INTO blogs (
                    author_id, title, slug, content, excerpt,
                    category, tags, status, published_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $title,
                $slug,
                $content,
                $excerpt ?: null,
                $category ?: null,
                $tags ?: null,
                $status,
                $published_date
            ]);
            
            $success = true;
            $post_id = $pdo->lastInsertId();
            $action = 'list';
            
        } catch (PDOException $e) {
            $errors[] = "Failed to create post. Please try again.";
            error_log("Create blog error: " . $e->getMessage());
        }
    }
}

// Handle Delete Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
    $delete_id = intval($_POST['delete_post_id']);
    
    try {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT author_id FROM blogs WHERE id = ?");
        $stmt->execute([$delete_id]);
        $post = $stmt->fetch();
        
        if ($post && $post['author_id'] === $user_id) {
            $stmt = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
            $stmt->execute([$delete_id]);
            $success = true;
        } else {
            $errors[] = "You can only delete your own posts";
        }
    } catch (PDOException $e) {
        $errors[] = "Failed to delete post.";
        error_log("Delete blog error: " . $e->getMessage());
    }
}

// Fetch user's blog posts
try {
    $stmt = $pdo->prepare("
        SELECT * FROM blogs
        WHERE author_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $user_posts = $stmt->fetchAll();
    
    // Fetch published posts from all users
    $stmt = $pdo->prepare("
        SELECT b.*, u.first_name, u.last_name
        FROM blogs b
        JOIN users u ON b.author_id = u.id
        WHERE b.status = 'published'
        ORDER BY b.published_date DESC
    ");
    $stmt->execute();
    $all_published_posts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Fetch blog posts error: " . $e->getMessage());
    $user_posts = [];
    $all_published_posts = [];
}

?>

<div style="margin-bottom: var(--spacing-2xl);">
    <h2>📝 Blog</h2>
    <p style="color: var(--color-dark-gray);">Share your thoughts, ideas, and experiences with the community</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <strong>Success!</strong>
        <p>Your blog post has been saved successfully.</p>
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

<?php if ($action === 'create'): ?>
    <!-- Create Post Form -->
    <div class="card" style="margin-bottom: var(--spacing-lg);">
        <div class="card-header">
            <h3>✍️ Write New Post</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="blog.php?action=create">
                <div class="form-group">
                    <label for="title">Post Title</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        placeholder="Give your post an engaging title..."
                        maxlength="255"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="excerpt">Excerpt (Summary)</label>
                    <input 
                        type="text" 
                        id="excerpt" 
                        name="excerpt" 
                        placeholder="Brief summary of your post (optional)"
                        maxlength="500"
                    >
                </div>
                
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea 
                        id="content" 
                        name="content"
                        placeholder="Write your blog post content here..."
                        required
                        style="min-height: 300px;"
                    ></textarea>
                    <small style="color: var(--color-dark-gray);">Supports basic HTML and Markdown</small>
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="category">Category (Optional)</label>
                        <input 
                            type="text" 
                            id="category" 
                            name="category" 
                            placeholder="e.g., Technology, Life, Tips"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="tags">Tags (Optional)</label>
                        <input 
                            type="text" 
                            id="tags" 
                            name="tags" 
                            placeholder="e.g., tech, coding, tutorial (comma-separated)"
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="status">Publish Status</label>
                    <select id="status" name="status" required>
                        <option value="draft">Save as Draft</option>
                        <option value="published">Publish Now</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: var(--spacing-md);">
                    <button type="submit" class="btn btn-primary">Save Post</button>
                    <a href="blog.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
<?php else: ?>
    <!-- View Posts -->
    <div class="grid grid-3">
        <!-- My Posts Sidebar -->
        <div class="card">
            <div class="card-header">
                <h3>📌 My Posts</h3>
            </div>
            <div class="card-body">
                <a href="blog.php?action=create" class="btn btn-primary btn-small btn-block" style="margin-bottom: var(--spacing-md);">
                    ✍️ Write New Post
                </a>
                
                <?php if (!empty($user_posts)): ?>
                    <div style="border-top: 1px solid #eee; padding-top: var(--spacing-md);">
                        <h4 style="margin-bottom: var(--spacing-md);">My Articles</h4>
                        <?php foreach ($user_posts as $post): ?>
                            <div style="margin-bottom: var(--spacing-lg); padding-bottom: var(--spacing-md); border-bottom: 1px solid #eee;">
                                <p style="margin: 0; font-weight: 500; font-size: var(--font-size-small);">
                                    <?php echo htmlspecialchars(substr($post['title'], 0, 30)); ?>...
                                </p>
                                <small style="color: var(--color-dark-gray);">
                                    <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                </small>
                                <div style="margin-top: var(--spacing-sm);">
                                    <span class="badge" style="
                                        background-color: <?php echo $post['status'] === 'published' ? '#dcfce7' : '#f3e8ff'; ?>;
                                        color: <?php echo $post['status'] === 'published' ? '#166534' : '#5b21b6'; ?>;
                                    ">
                                        <?php echo ucfirst($post['status']); ?>
                                    </span>
                                </div>
                                <div style="margin-top: var(--spacing-sm); display: flex; gap: var(--spacing-sm);">
                                    <a href="blog.php?action=edit&id=<?php echo $post['id']; ?>" class="btn btn-small btn-secondary">Edit</a>
                                    <form method="POST" action="blog.php" style="display: inline;">
                                        <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this post?');">
                                            Delete
                                        </button>
                                        <input type="hidden" name="delete_post_id" value="<?php echo $post['id']; ?>">
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--color-dark-gray); text-align: center; padding: var(--spacing-lg) 0;">
                        No posts yet. Write your first post! ✍️
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Published Posts Feed -->
        <div style="grid-column: span 2;">
            <div class="card">
                <div class="card-header">
                    <h3>🌍 Community Posts</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($all_published_posts)): ?>
                        <?php foreach ($all_published_posts as $post): ?>
                            <div style="padding: var(--spacing-lg); border-bottom: 1px solid #eee; display: flex; gap: var(--spacing-lg);">
                                <div style="flex: 1;">
                                    <h4 style="margin-top: 0; color: var(--color-deep-blue);">
                                        <a href="blog.php?action=view&id=<?php echo $post['id']; ?>">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h4>
                                    <small style="color: var(--color-dark-gray);">
                                        By <strong><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></strong>
                                        • <?php echo date('M d, Y', strtotime($post['published_date'])); ?>
                                    </small>
                                    <p style="margin: var(--spacing-md) 0; color: var(--color-dark-gray);">
                                        <?php echo htmlspecialchars(substr($post['excerpt'] ?? $post['content'], 0, 150)); ?>...
                                    </p>
                                    <div style="display: flex; gap: var(--spacing-md); align-items: center;">
                                        <?php if ($post['category']): ?>
                                            <span class="badge badge-info">
                                                <?php echo htmlspecialchars($post['category']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <small>
                                            👁️ <?php echo $post['view_count']; ?> views
                                            • ❤️ <?php echo $post['like_count']; ?> likes
                                            • 💬 <?php echo $post['comment_count']; ?> comments
                                        </small>
                                    </div>
                                </div>
                                <a href="blog.php?action=view&id=<?php echo $post['id']; ?>" class="btn btn-secondary btn-small" style="height: fit-content;">
                                    Read →
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--color-dark-gray); padding: var(--spacing-2xl) 0;">
                            No published posts yet. Be the first to share! 📝
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
<?php endif; ?>

<?php
// Include footer
require_once '../../private/includes/footer.php';
?>
