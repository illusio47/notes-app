/**
 * Voice Recorder Module for Notes App
 * Handles audio recording, visualization, and speech-to-text transcription
 */

class VoiceRecorder {
    constructor() {
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.audioBlob = null;
        this.audioUrl = null;
        this.stream = null;
        this.recognition = null;
        this.isRecording = false;
        this.timerInterval = null;
        this.recordingTime = 0;
        this.finalTranscript = '';
        this.interimTranscript = '';
        this.audioContext = null;
        this.analyser = null;
        this.animationId = null;
        
        this.init();
    }
    
    init() {
        // Get DOM elements
        this.startBtn = document.getElementById('startRecording');
        this.stopBtn = document.getElementById('stopRecording');
        this.statusIndicator = document.getElementById('statusIndicator');
        this.statusText = document.getElementById('statusText');
        this.timerDisplay = document.getElementById('recordingTimer');
        this.audioPlayback = document.getElementById('audioPlayback');
        this.audioPlayer = document.getElementById('audioPlayer');
        this.visualizerCanvas = document.getElementById('visualizerCanvas');
        this.interimTranscriptEl = document.getElementById('interimTranscript');
        this.finalTranscriptEl = document.getElementById('finalTranscript');
        this.voiceNoteForm = document.getElementById('voiceNoteForm');
        this.transcriptionInput = document.getElementById('transcriptionInput');
        this.audioDataInput = document.getElementById('audioDataInput');
        this.editableContent = document.getElementById('editableContent');
        this.newRecordingBtn = document.getElementById('newRecording');
        this.browserNotice = document.getElementById('browserNotice');
        
        // Check browser support
        this.checkBrowserSupport();
        
        // Bind event listeners
        this.bindEvents();
    }
    
    checkBrowserSupport() {
        // Check for MediaRecorder support
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            this.showError('Your browser does not support audio recording.');
            this.startBtn.disabled = true;
            return false;
        }
        
        // Check for Speech Recognition support
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            this.browserNotice.style.display = 'block';
        }
        
        return true;
    }
    
    bindEvents() {
        this.startBtn.addEventListener('click', () => this.startRecording());
        this.stopBtn.addEventListener('click', () => this.stopRecording());
        this.newRecordingBtn.addEventListener('click', () => this.resetRecorder());
        
        // Form submission handling
        this.voiceNoteForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
    }
    
    async startRecording() {
        try {
            // Request microphone access
            this.stream = await navigator.mediaDevices.getUserMedia({ 
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                }
            });
            
            // Initialize MediaRecorder
            const mimeType = this.getSupportedMimeType();
            this.mediaRecorder = new MediaRecorder(this.stream, { mimeType });
            this.audioChunks = [];
            
            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.audioChunks.push(event.data);
                }
            };
            
            this.mediaRecorder.onstop = () => this.processRecording();
            
            // Start recording
            this.mediaRecorder.start(100); // Collect data every 100ms
            this.isRecording = true;
            
            // Update UI
            this.updateUIForRecording();
            
            // Start timer
            this.startTimer();
            
            // Start audio visualization
            this.startVisualization();
            
            // Start speech recognition
            this.startSpeechRecognition();
            
        } catch (error) {
            console.error('Error starting recording:', error);
            this.showError('Could not access microphone. Please check permissions.');
        }
    }
    
    getSupportedMimeType() {
        const mimeTypes = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/ogg;codecs=opus',
            'audio/mp4',
            'audio/mpeg'
        ];
        
        for (const mimeType of mimeTypes) {
            if (MediaRecorder.isTypeSupported(mimeType)) {
                return mimeType;
            }
        }
        
        return 'audio/webm'; // Default fallback
    }
    
    stopRecording() {
        if (this.mediaRecorder && this.isRecording) {
            this.mediaRecorder.stop();
            this.isRecording = false;
            
            // Stop all tracks
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }
            
            // Stop timer
            this.stopTimer();
            
            // Stop visualization
            this.stopVisualization();
            
            // Stop speech recognition
            this.stopSpeechRecognition();
            
            // Update UI
            this.updateUIForStopped();
        }
    }
    
    processRecording() {
        // Create audio blob
        const mimeType = this.mediaRecorder.mimeType || 'audio/webm';
        this.audioBlob = new Blob(this.audioChunks, { type: mimeType });
        this.audioUrl = URL.createObjectURL(this.audioBlob);
        
        // Set audio player source
        this.audioPlayer.src = this.audioUrl;
        this.audioPlayback.style.display = 'block';
        
        // Convert audio to base64 for form submission
        this.convertBlobToBase64(this.audioBlob);
        
        // Show the form
        this.showSaveForm();
    }
    
    convertBlobToBase64(blob) {
        const reader = new FileReader();
        reader.onloadend = () => {
            this.audioDataInput.value = reader.result;
        };
        reader.readAsDataURL(blob);
    }
    
    startSpeechRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        
        if (!SpeechRecognition) {
            console.log('Speech recognition not supported');
            return;
        }
        
        this.recognition = new SpeechRecognition();
        this.recognition.continuous = true;
        this.recognition.interimResults = true;
        this.recognition.lang = 'en-US';
        
        this.recognition.onresult = (event) => {
            this.interimTranscript = '';
            
            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                
                if (event.results[i].isFinal) {
                    this.finalTranscript += transcript + ' ';
                } else {
                    this.interimTranscript += transcript;
                }
            }
            
            this.updateTranscriptionDisplay();
        };
        
        this.recognition.onerror = (event) => {
            console.log('Speech recognition error:', event.error);
            if (event.error === 'no-speech') {
                // This is common, don't show error
                return;
            }
        };
        
        this.recognition.onend = () => {
            // Restart recognition if still recording
            if (this.isRecording && this.recognition) {
                try {
                    this.recognition.start();
                } catch (e) {
                    // Recognition already started
                }
            }
        };
        
        try {
            this.recognition.start();
        } catch (e) {
            console.log('Could not start speech recognition:', e);
        }
    }
    
    stopSpeechRecognition() {
        if (this.recognition) {
            this.recognition.stop();
            this.recognition = null;
        }
    }
    
    updateTranscriptionDisplay() {
        this.finalTranscriptEl.textContent = this.finalTranscript;
        this.interimTranscriptEl.textContent = this.interimTranscript;
    }
    
    startVisualization() {
        this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        this.analyser = this.audioContext.createAnalyser();
        const source = this.audioContext.createMediaStreamSource(this.stream);
        
        source.connect(this.analyser);
        this.analyser.fftSize = 256;
        
        const canvas = this.visualizerCanvas;
        const canvasCtx = canvas.getContext('2d');
        const bufferLength = this.analyser.frequencyBinCount;
        const dataArray = new Uint8Array(bufferLength);
        
        // Set canvas size
        canvas.width = canvas.parentElement.offsetWidth;
        canvas.height = 100;
        
        const draw = () => {
            this.animationId = requestAnimationFrame(draw);
            
            this.analyser.getByteFrequencyData(dataArray);
            
            canvasCtx.fillStyle = '#f8fafc';
            canvasCtx.fillRect(0, 0, canvas.width, canvas.height);
            
            const barWidth = (canvas.width / bufferLength) * 2.5;
            let barHeight;
            let x = 0;
            
            for (let i = 0; i < bufferLength; i++) {
                barHeight = (dataArray[i] / 255) * canvas.height;
                
                // Create gradient effect
                const gradient = canvasCtx.createLinearGradient(0, canvas.height - barHeight, 0, canvas.height);
                gradient.addColorStop(0, '#6366f1');
                gradient.addColorStop(1, '#8b5cf6');
                
                canvasCtx.fillStyle = gradient;
                canvasCtx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
                
                x += barWidth + 1;
            }
        };
        
        draw();
    }
    
    stopVisualization() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }
        
        if (this.audioContext) {
            this.audioContext.close();
            this.audioContext = null;
        }
        
        // Clear canvas
        const canvas = this.visualizerCanvas;
        const canvasCtx = canvas.getContext('2d');
        canvasCtx.clearRect(0, 0, canvas.width, canvas.height);
    }
    
    startTimer() {
        this.recordingTime = 0;
        this.updateTimerDisplay();
        
        this.timerInterval = setInterval(() => {
            this.recordingTime++;
            this.updateTimerDisplay();
        }, 1000);
    }
    
    stopTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    }
    
    updateTimerDisplay() {
        const minutes = Math.floor(this.recordingTime / 60);
        const seconds = this.recordingTime % 60;
        this.timerDisplay.textContent = 
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
    
    updateUIForRecording() {
        this.startBtn.disabled = true;
        this.stopBtn.disabled = false;
        this.statusIndicator.classList.add('recording');
        this.statusText.textContent = 'Recording...';
    }
    
    updateUIForStopped() {
        this.startBtn.disabled = false;
        this.stopBtn.disabled = true;
        this.statusIndicator.classList.remove('recording');
        this.statusText.textContent = 'Recording complete';
    }
    
    showSaveForm() {
        this.voiceNoteForm.style.display = 'block';
        this.transcriptionInput.value = this.finalTranscript.trim();
        this.editableContent.value = this.finalTranscript.trim();
        
        // Generate suggested title from first few words
        const words = this.finalTranscript.trim().split(' ').slice(0, 5);
        const suggestedTitle = words.length > 0 ? words.join(' ') + '...' : 'Voice Note ' + new Date().toLocaleDateString();
        document.getElementById('noteTitle').value = suggestedTitle;
    }
    
    resetRecorder() {
        // Reset all state
        this.audioChunks = [];
        this.audioBlob = null;
        this.audioUrl = null;
        this.finalTranscript = '';
        this.interimTranscript = '';
        this.recordingTime = 0;
        
        // Reset UI
        this.audioPlayback.style.display = 'none';
        this.voiceNoteForm.style.display = 'none';
        this.finalTranscriptEl.textContent = '';
        this.interimTranscriptEl.textContent = '';
        this.updateTimerDisplay();
        this.statusText.textContent = 'Ready to record';
        this.startBtn.disabled = false;
        this.stopBtn.disabled = true;
        
        // Clear form
        document.getElementById('noteTitle').value = '';
        this.editableContent.value = '';
        this.transcriptionInput.value = '';
        this.audioDataInput.value = '';
    }
    
    handleFormSubmit(e) {
        const title = document.getElementById('noteTitle').value.trim();
        
        if (!title) {
            e.preventDefault();
            this.showError('Please enter a title for your note.');
            return false;
        }
        
        // Update transcription with edited content
        this.transcriptionInput.value = this.editableContent.value;
        
        return true;
    }
    
    showError(message) {
        // Create error alert
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger';
        alert.textContent = message;
        
        const container = document.querySelector('.voice-note-container');
        const firstChild = container.querySelector('h2');
        container.insertBefore(alert, firstChild.nextSibling);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new VoiceRecorder();
});
