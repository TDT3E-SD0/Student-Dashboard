<?php
/**
 * File Manager & Google Drive Integration
 * 
 * Features:
 * - File Manager UI for local and cloud files
 * - Google Drive integration (API placeholder)
 * - Upload interface
 * - File sharing controls
 * - Storage statistics
 */

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['status'] !== 'active') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Files";

// Include header
require_once '../../private/includes/header.php';

// Database connection
require_once '../../private/config/db_connect.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

/**
 * Placeholder function for Google Drive upload
 * To be implemented with actual Google Drive API
 */
function uploadToDrive($file_path, $file_name, $auth_token = null) {
    // TODO: Implement Google Drive API integration
    // This will use the Google Drive API to upload files
    
    $response = [
        'success' => false,
        'message' => 'Google Drive integration coming soon',
        'drive_id' => null,
        'drive_url' => null
    ];
    
    /*
    // Example implementation (requires google/apiclient)
    if (!$auth_token) {
        $response['message'] = 'Authentication token required';
        return $response;
    }
    
    try {
        $client = new Google_Client();
        $client->setAccessToken($auth_token);
        $service = new Google_Service_Drive($client);
        
        $fileMetadata = new Google_Service_DriveFile(array(
            'name' => $file_name
        ));
        
        $content = file_get_contents($file_path);
        $file = $service->files->create($fileMetadata, array(
            'data' => $content,
            'mimeType' => mime_content_type($file_path),
            'uploadType' => 'multipart',
            'fields' => 'id'
        ));
        
        $response['success'] = true;
        $response['message'] = 'File uploaded to Google Drive';
        $response['drive_id'] = $file->id;
        $response['drive_url'] = 'https://drive.google.com/file/d/' . $file->id;
        
    } catch (Exception $e) {
        $response['message'] = 'Drive upload failed: ' . $e->getMessage();
    }
    */
    
    return $response;
}

// Fetch user files
try {
    $stmt = $pdo->prepare("
        SELECT * FROM files
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $user_files = $stmt->fetchAll();
    
    // Calculate storage stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_files,
            SUM(file_size) as total_size,
            SUM(CASE WHEN storage_type = 'local' THEN 1 ELSE 0 END) as local_files,
            SUM(CASE WHEN storage_type = 'google-drive' THEN 1 ELSE 0 END) as drive_files
        FROM files
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $storage_stats = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Fetch files error: " . $e->getMessage());
    $user_files = [];
    $storage_stats = [
        'total_files' => 0,
        'total_size' => 0,
        'local_files' => 0,
        'drive_files' => 0
    ];
}

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

?>

<div style="margin-bottom: var(--spacing-2xl);">
    <h2>📁 File Manager</h2>
    <p style="color: var(--color-dark-gray);">Manage your files and integrate with Google Drive</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <strong>Success!</strong>
        <p>File operation completed successfully.</p>
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

<!-- Storage Overview -->
<div class="grid grid-4">
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Total Files</span>
                <span class="stat-value"><?php echo $storage_stats['total_files']; ?></span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Total Size</span>
                <span class="stat-value" style="font-size: var(--font-size-base);">
                    <?php echo formatBytes($storage_stats['total_size'] ?? 0); ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Local Files</span>
                <span class="stat-value"><?php echo $storage_stats['local_files']; ?></span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="stat-item">
                <span class="stat-label">Drive Files</span>
                <span class="stat-value"><?php echo $storage_stats['drive_files']; ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Upload Section -->
<div class="grid grid-2" style="margin-bottom: var(--spacing-lg);">
    <!-- Local Upload -->
    <div class="card">
        <div class="card-header">
            <h3>⬆️ Upload to Local Storage</h3>
        </div>
        <div class="card-body">
            <form enctype="multipart/form-data">
                <div class="form-group">
                    <label for="local_file">Select File</label>
                    <input 
                        type="file" 
                        id="local_file" 
                        name="file"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.png,.zip"
                    >
                    <small style="color: var(--color-dark-gray);">
                        Max 50MB. Supported: PDF, Office, Images, ZIP
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="local_category">Category</label>
                    <select id="local_category" name="category">
                        <option value="">-- Select Category --</option>
                        <option value="assignments">Assignments</option>
                        <option value="notes">Notes</option>
                        <option value="projects">Projects</option>
                        <option value="resources">Resources</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Upload File</button>
            </form>
        </div>
    </div>
    
    <!-- Google Drive Upload -->
    <div class="card">
        <div class="card-header">
            <h3>☁️ Sync with Google Drive</h3>
        </div>
        <div class="card-body">
            <p style="margin-bottom: var(--spacing-md); color: var(--color-dark-gray);">
                Connect your Google Drive account to sync and backup files automatically.
            </p>
            
            <div style="padding: var(--spacing-lg); background-color: #f0f9ff; border-radius: var(--border-radius); margin-bottom: var(--spacing-md); border-left: 4px solid var(--color-neon-blue);">
                <p style="margin: 0; color: var(--color-dark-gray);">
                    <strong>Status:</strong> Not connected
                </p>
            </div>
            
            <button type="button" class="btn btn-secondary btn-block" onclick="alert('Google Drive API integration coming soon!');">
                🔗 Connect to Google Drive
            </button>
            
            <p style="font-size: var(--font-size-small); margin-top: var(--spacing-md); text-align: center; color: var(--color-dark-gray);">
                We respect your privacy. No data is stored on our servers.
            </p>
        </div>
    </div>
</div>

<!-- Files List -->
<div class="card">
    <div class="card-header">
        <h3>📋 Your Files</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($user_files)): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Category</th>
                            <th>Size</th>
                            <th>Storage</th>
                            <th>Uploaded</th>
                            <th>Visibility</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_files as $file): ?>
                            <tr>
                                <td>
                                    <strong>📄 <?php echo htmlspecialchars($file['file_name']); ?></strong>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($file['category'] ?? '-'); ?></small>
                                </td>
                                <td>
                                    <small><?php echo formatBytes($file['file_size']); ?></small>
                                </td>
                                <td>
                                    <span class="badge" style="
                                        background-color: <?php echo $file['storage_type'] === 'google-drive' ? '#e0f2fe' : '#f3e8ff'; ?>;
                                        color: <?php echo $file['storage_type'] === 'google-drive' ? '#0c2d6b' : '#5b21b6'; ?>;
                                    ">
                                        <?php 
                                        echo $file['storage_type'] === 'google-drive' ? '☁️ Drive' : '💾 Local';
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('M d, Y', strtotime($file['created_at'])); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $file['visibility'] === 'public' ? 'success' : 'info'; ?>">
                                        <?php echo ucfirst($file['visibility']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: var(--spacing-sm);">
                                        <button class="btn btn-small btn-secondary" title="Download">⬇️</button>
                                        <button class="btn btn-small btn-secondary" title="Share">🔗</button>
                                        <button class="btn btn-small btn-danger" title="Delete" onclick="return confirm('Delete this file?');">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: var(--spacing-2xl);">
                <p style="color: var(--color-dark-gray); margin-bottom: var(--spacing-md);">
                    📁 No files uploaded yet. Start by uploading your first file!
                </p>
                <a href="#upload" class="btn btn-primary">⬆️ Upload File</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- File Sharing & Collaboration -->
<div class="card" style="margin-top: var(--spacing-lg);">
    <div class="card-header">
        <h3>🤝 Shared with Me</h3>
    </div>
    <div class="card-body">
        <p style="text-align: center; color: var(--color-dark-gray); padding: var(--spacing-lg);">
            📭 No files shared with you yet.
        </p>
    </div>
</div>

<!-- Storage Usage Info -->
<div class="alert alert-info" style="margin-top: var(--spacing-lg);">
    <strong>ℹ️ Storage Information</strong>
    <p>You're using <?php echo formatBytes($storage_stats['total_size'] ?? 0); ?> of 5GB available storage.</p>
    <p>Consider archiving or deleting old files to free up space.</p>
</div>

<?php
// Include footer
require_once '../../private/includes/footer.php';
?>
