<?php
require_once 'includes/header.php';
requireAuth();

$error = '';
$user_id = getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    
    if (empty($title)) {
        $error = 'Title is required';
    } else {
        $stmt = $conn->prepare("INSERT INTO notes (user_id, title, content, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("iss", $user_id, $title, $content);
        
        if ($stmt->execute()) {
            redirectWithMessage('index.php', 'Note created successfully!');
        } else {
            $error = 'Failed to create note. Please try again.';
        }
        $stmt->close();
    }
}
?>

<div class="note-form-container">
    <h2>Create New Note</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required>
        </div>
        
        <div class="form-group">
            <label for="content">Content</label>
            <textarea id="content" name="content" rows="10"></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Note</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
