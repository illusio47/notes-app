<?php
require_once 'config/db.php';
session_start();
require_once 'includes/auth.php';
requireAuth();

$user_id = getCurrentUserId();
$note_id = intval($_GET['id'] ?? 0);

// Soft delete - move to trash
$stmt = $conn->prepare("UPDATE notes SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $note_id, $user_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    redirectWithMessage('index.php', 'Note moved to trash');
} else {
    redirectWithMessage('index.php', 'Failed to delete note', 'danger');
}

$stmt->close();
?>
