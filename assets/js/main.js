// Main JavaScript file for Notes App

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.btn-danger');
    deleteButtons.forEach(function(button) {
        if (!button.hasAttribute('onclick')) {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure?')) {
                    e.preventDefault();
                }
            });
        }
    });

    // Auto-resize textarea
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(function(textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });

    // Character counter for note content
    const contentTextarea = document.getElementById('content');
    if (contentTextarea) {
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.style.cssText = 'text-align: right; font-size: 0.8rem; color: #999; margin-top: 0.25rem;';
        contentTextarea.parentNode.appendChild(counter);
        
        function updateCounter() {
            counter.textContent = contentTextarea.value.length + ' characters';
        }
        
        contentTextarea.addEventListener('input', updateCounter);
        updateCounter();
    }
});
