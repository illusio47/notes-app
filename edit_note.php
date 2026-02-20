<?php
require_once 'includes/header.php';
requireAuth();

$user_id = getCurrentUserId();
$note_id = intval($_GET['id'] ?? 0);
$error = '';

// Get note
$stmt = $conn->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->bind_param("ii", $note_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$note = $result->fetch_assoc();
$stmt->close();

if (!$note) {
    redirectWithMessage('index.php', 'Note not found', 'danger');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    
    if (empty($title)) {
        $error = 'Title is required';
    } else {
        $stmt = $conn->prepare("UPDATE notes SET title = ?, content = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $title, $content, $note_id, $user_id);
        
        if ($stmt->execute()) {
            redirectWithMessage('index.php', 'Note updated successfully!');
        } else {
            $error = 'Failed to update note. Please try again.';
        }
        $stmt->close();
    }
}
?>

<div class="note-form-container">
    <h2>Edit Note</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?php echo sanitize($note['title']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="content">Content</label>
            <textarea id="content" name="content" rows="10"><?php echo sanitize($note['content']); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Note</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
