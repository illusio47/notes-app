<?php
/**
 * Secure audio file server
 * Only serves audio files to the owning user
 */
require_once 'includes/header.php';
requireAuth();

$user_id = getCurrentUserId();
$filename = $_GET['file'] ?? '';

if (empty($filename)) {
    http_response_code(400);
    echo 'Bad request';
    exit;
}

// Sanitize filename - only allow alphanumeric, underscore, dot
$filename = preg_replace('/[^a-zA-Z0-9_\.]/', '', $filename);

// Check if filename matches expected pattern and belongs to user
if (!preg_match('/^voice_' . $user_id . '_\d+_[a-f0-9]+\.\w+$/', $filename)) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

$filepath = __DIR__ . '/uploads/audio/' . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

// Determine content type
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$content_types = [
    'webm' => 'audio/webm',
    'ogg' => 'audio/ogg',
    'mp4' => 'audio/mp4',
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav'
];

$content_type = $content_types[$extension] ?? 'application/octet-stream';

// Serve the file
header('Content-Type: ' . $content_type);
header('Content-Length: ' . filesize($filepath));
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Cache-Control: private, max-age=3600');

readfile($filepath);
exit;
?>
