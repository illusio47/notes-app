<?php
require_once 'includes/header.php';
requireAuth();

$error = '';
$user_id = getCurrentUserId();
?>

<div class="voice-note-container">
    <h2>Voice Note</h2>
    <p class="voice-description">Record your voice and it will be transcribed and saved as a note.</p>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="voice-recorder">
        <!-- Recording Controls -->
        <div class="recorder-controls">
            <button type="button" id="startRecording" class="btn btn-record">
                <span class="record-icon">🎤</span>
                Start Recording
            </button>
            <button type="button" id="stopRecording" class="btn btn-stop" disabled>
                <span class="stop-icon">⏹️</span>
                Stop Recording
            </button>
        </div>
        
        <!-- Recording Status -->
        <div class="recording-status" id="recordingStatus">
            <div class="status-indicator" id="statusIndicator"></div>
            <span id="statusText">Ready to record</span>
        </div>
        
        <!-- Recording Timer -->
        <div class="recording-timer" id="recordingTimer">00:00</div>
        
        <!-- Audio Visualization -->
        <div class="audio-visualizer" id="audioVisualizer">
            <canvas id="visualizerCanvas"></canvas>
        </div>
        
        <!-- Audio Playback -->
        <div class="audio-playback" id="audioPlayback" style="display: none;">
            <h4>Recording Preview</h4>
            <audio id="audioPlayer" controls></audio>
        </div>
        
        <!-- Live Transcription -->
        <div class="transcription-area">
            <h4>Live Transcription</h4>
            <div class="transcription-box" id="transcriptionBox">
                <p id="interimTranscript" class="interim-text"></p>
                <p id="finalTranscript" class="final-text"></p>
            </div>
        </div>
        
        <!-- Save Form -->
        <form id="voiceNoteForm" method="POST" action="save_voice_note.php" style="display: none;">
            <input type="hidden" name="transcription" id="transcriptionInput">
            <input type="hidden" name="audio_data" id="audioDataInput">
            
            <div class="form-group">
                <label for="noteTitle">Note Title</label>
                <input type="text" id="noteTitle" name="title" placeholder="Enter a title for your note" required>
            </div>
            
            <div class="form-group">
                <label for="editableContent">Edit Transcription (optional)</label>
                <textarea id="editableContent" name="content" rows="6"></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="save_audio" id="saveAudio" value="1">
                    Also save audio file
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Note</button>
                <button type="button" id="newRecording" class="btn btn-secondary">New Recording</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <!-- Browser Support Notice -->
    <div class="browser-notice" id="browserNotice" style="display: none;">
        <div class="alert alert-warning">
            <strong>Notice:</strong> Your browser may not fully support speech recognition. 
            Audio recording will still work, but live transcription may not be available.
            For best experience, use Google Chrome.
        </div>
    </div>
</div>

<script src="assets/js/voice-recorder.js"></script>

<?php require_once 'includes/footer.php'; ?>
