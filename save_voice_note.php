<?php
require_once 'includes/header.php';
requireAuth();

$user_id = getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: voice_note.php');
    exit;
}

$title = sanitize($_POST['title'] ?? '');
$content = $_POST['content'] ?? '';
$save_audio = isset($_POST['save_audio']) && $_POST['save_audio'] == '1';
$audio_data = $_POST['audio_data'] ?? '';

if (empty($title)) {
    redirectWithMessage('voice_note.php', 'Title is required', 'error');
    exit;
}

$audio_filename = null;

// Handle audio file saving if requested
if ($save_audio && !empty($audio_data)) {
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/uploads/audio/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Decode base64 audio data
    // Format: data:audio/webm;base64,XXXX...
    if (preg_match('/^data:audio\/(\w+);base64,/', $audio_data, $matches)) {
        $audio_format = $matches[1];
        $audio_data = preg_replace('/^data:audio\/\w+;base64,/', '', $audio_data);
        $audio_data = base64_decode($audio_data);
        
        if ($audio_data !== false) {
            // Generate unique filename
            $audio_filename = 'voice_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $audio_format;
            $audio_path = $upload_dir . $audio_filename;
            
            // Save the audio file
            if (file_put_contents($audio_path, $audio_data) === false) {
                $audio_filename = null; // Failed to save
            }
        }
    }
}

// Prepare content with voice note marker
$note_content = $content;
if ($audio_filename) {
    // Add audio reference to content
    $note_content = "[🎤 Voice Note]\n\n" . $content . "\n\n[Audio: " . $audio_filename . "]";
}

// Check if audio_file column exists, if not use regular content
$has_audio_column = false;
$check_column = $conn->query("SHOW COLUMNS FROM notes LIKE 'audio_file'");
if ($check_column && $check_column->num_rows > 0) {
    $has_audio_column = true;
}

// Save the note
if ($has_audio_column && $audio_filename) {
    $stmt = $conn->prepare("INSERT INTO notes (user_id, title, content, audio_file, is_voice_note, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())");
    $stmt->bind_param("isss", $user_id, $title, $content, $audio_filename);
} else {
    $stmt = $conn->prepare("INSERT INTO notes (user_id, title, content, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("iss", $user_id, $title, $note_content);
}

if ($stmt->execute()) {
    redirectWithMessage('index.php', 'Voice note saved successfully!');
} else {
    redirectWithMessage('voice_note.php', 'Failed to save voice note. Please try again.', 'error');
}

$stmt->close();
?>
