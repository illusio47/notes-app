<?php
require_once 'includes/header.php';
requireAuth();

$user_id = getCurrentUserId();

// Handle restore or permanent delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note_id = intval($_POST['note_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($action === 'restore') {
        $stmt = $conn->prepare("UPDATE notes SET is_deleted = 0, deleted_at = NULL WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $note_id, $user_id);
        if ($stmt->execute()) {
            redirectWithMessage('trash.php', 'Note restored successfully');
        }
    } elseif ($action === 'permanent_delete') {
        $stmt = $conn->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $note_id, $user_id);
        if ($stmt->execute()) {
            redirectWithMessage('trash.php', 'Note permanently deleted');
        }
    } elseif ($action === 'empty_trash') {
        $stmt = $conn->prepare("DELETE FROM notes WHERE user_id = ? AND is_deleted = 1");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            redirectWithMessage('trash.php', 'Trash emptied');
        }
    }
}

// Get deleted notes
$stmt = $conn->prepare("SELECT * FROM notes WHERE user_id = ? AND is_deleted = 1 ORDER BY deleted_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notes = $stmt->get_result();
?>

<div class="notes-container">
    <div class="page-header">
        <h2>Trash</h2>
        <?php if ($notes->num_rows > 0): ?>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="empty_trash">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Permanently delete all notes in trash?')">Empty Trash</button>
            </form>
        <?php endif; ?>
    </div>
    
    <?php echo displayFlashMessage(); ?>
    
    <?php if ($notes->num_rows > 0): ?>
        <div class="notes-grid">
            <?php while ($note = $notes->fetch_assoc()): ?>
                <div class="note-card deleted">
                    <h3><?php echo sanitize($note['title']); ?></h3>
                    <p class="note-preview"><?php echo substr(sanitize($note['content']), 0, 150); ?>...</p>
                    <div class="note-meta">
                        <span class="note-date">Deleted: <?php echo date('M d, Y', strtotime($note['deleted_at'])); ?></span>
                    </div>
                    <div class="note-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                            <input type="hidden" name="action" value="restore">
                            <button type="submit" class="btn btn-small btn-success">Restore</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                            <input type="hidden" name="action" value="permanent_delete">
                            <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Permanently delete?')">Delete Forever</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>Trash is empty</p>
        </div>
    <?php endif; ?>
</div>

<?php 
$stmt->close();
require_once 'includes/footer.php'; 
?>
