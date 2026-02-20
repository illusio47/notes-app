<?php
require_once 'includes/header.php';
requireAuth();

$user_id = getCurrentUserId();

// Get all non-deleted notes for current user
$stmt = $conn->prepare("SELECT * FROM notes WHERE user_id = ? AND is_deleted = 0 ORDER BY updated_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notes = $stmt->get_result();
?>

<div class="notes-container">
    <div class="page-header">
        <h2>My Notes</h2>
        <a href="create_note.php" class="btn btn-primary">+ New Note</a>
    </div>
    
    <?php echo displayFlashMessage(); ?>
    
    <?php if ($notes->num_rows > 0): ?>
        <div class="notes-grid">
            <?php while ($note = $notes->fetch_assoc()): ?>
                <div class="note-card">
                    <h3>
                        <?php 
                        // Check if it's a voice note
                        $isVoiceNote = (isset($note['is_voice_note']) && $note['is_voice_note']) || 
                                       strpos($note['content'], '[🎤 Voice Note]') === 0;
                        if ($isVoiceNote): 
                        ?>
                            <span class="voice-note-badge">🎤 Voice</span>
                        <?php endif; ?>
                        <?php echo sanitize($note['title']); ?>
                    </h3>
                    <p class="note-preview">
                        <?php 
                        $content = $note['content'];
                        // Clean up voice note markers for display
                        $content = preg_replace('/\[🎤 Voice Note\]\s*/', '', $content);
                        $content = preg_replace('/\[Audio: [^\]]+\]/', '', $content);
                        echo substr(sanitize($content), 0, 150); 
                        ?>...
                    </p>
                    <?php 
                    // Show audio player if audio file exists
                    if (isset($note['audio_file']) && !empty($note['audio_file'])): 
                    ?>
                        <div class="note-audio">
                            <audio controls>
                                <source src="get_audio.php?file=<?php echo urlencode($note['audio_file']); ?>">
                                Your browser does not support audio playback.
                            </audio>
                        </div>
                    <?php endif; ?>
                    <div class="note-meta">
                        <span class="note-date"><?php echo date('M d, Y', strtotime($note['updated_at'])); ?></span>
                    </div>
                    <div class="note-actions">
                        <a href="edit_note.php?id=<?php echo $note['id']; ?>" class="btn btn-small">Edit</a>
                        <a href="delete_note.php?id=<?php echo $note['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Move to trash?')">Delete</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>No notes yet. <a href="create_note.php">Create your first note</a></p>
        </div>
    <?php endif; ?>
</div>

<?php 
$stmt->close();
require_once 'includes/footer.php'; 
?>
