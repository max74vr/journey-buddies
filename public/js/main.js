/**
 * Compagni di Viaggi - Main JavaScript
 */

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }

    // Auto-hide flash messages after 5 seconds
    const flashMessage = document.querySelector('.flash-message');
    if (flashMessage) {
        setTimeout(() => {
            flashMessage.style.opacity = '0';
            flashMessage.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                flashMessage.remove();
            }, 500);
        }, 5000);
    }

    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    showFieldError(field, 'Questo campo è obbligatorio');
                } else {
                    field.classList.remove('error');
                    removeFieldError(field);
                }
            });

            if (!isValid) {
                e.preventDefault();
            }
        });
    });

    // Image preview for file uploads
    const fileInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(input.dataset.preview);
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Dynamic form fields (multi-select)
    const addFieldButtons = document.querySelectorAll('[data-add-field]');
    addFieldButtons.forEach(button => {
        button.addEventListener('click', function() {
            const container = document.querySelector(button.dataset.addField);
            if (container) {
                const template = container.querySelector('[data-template]');
                if (template) {
                    const clone = template.cloneNode(true);
                    clone.removeAttribute('data-template');
                    clone.style.display = 'block';
                    container.appendChild(clone);
                }
            }
        });
    });

    // Confirmation dialogs
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = button.dataset.confirm || 'Sei sicuro?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Character counter for textareas
    const textareas = document.querySelectorAll('textarea[data-max-length]');
    textareas.forEach(textarea => {
        const maxLength = parseInt(textarea.dataset.maxLength);
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.textContent = `0 / ${maxLength}`;
        textarea.parentNode.appendChild(counter);

        textarea.addEventListener('input', function() {
            const length = textarea.value.length;
            counter.textContent = `${length} / ${maxLength}`;

            if (length > maxLength) {
                counter.style.color = 'var(--error-color)';
            } else {
                counter.style.color = 'var(--text-secondary)';
            }
        });
    });
});

// Helper functions
function showFieldError(field, message) {
    let errorDiv = field.parentNode.querySelector('.form-error');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'form-error';
        field.parentNode.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

function removeFieldError(field) {
    const errorDiv = field.parentNode.querySelector('.form-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Chat functionality
if (document.querySelector('.chat-container')) {
    initChat();
}

function initChat() {
    const chatContainer = document.querySelector('.chat-messages');
    const chatForm = document.querySelector('.chat-form');
    const chatInput = document.querySelector('#chat-message-input');
    const chatGroupId = document.querySelector('[data-chat-group-id]')?.dataset.chatGroupId;

    if (!chatGroupId) return;

    let lastMessageId = 0;

    // Get last message ID
    const messages = chatContainer.querySelectorAll('[data-message-id]');
    if (messages.length > 0) {
        lastMessageId = parseInt(messages[messages.length - 1].dataset.messageId);
    }

    // Send message
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const message = chatInput.value.trim();
            if (!message) return;

            const formData = new FormData();
            formData.append('chat_group_id', chatGroupId);
            formData.append('message', message);

            fetch('/api/send-message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    chatInput.value = '';
                    // Messages will be loaded by polling
                } else {
                    alert(data.error || 'Errore durante l\'invio del messaggio');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }

    // Poll for new messages every 3 seconds
    setInterval(() => {
        fetch(`/api/get-messages.php?chat_group_id=${chatGroupId}&last_message_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        appendMessage(msg);
                        lastMessageId = msg.id;
                    });
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }, 3000);
}

function appendMessage(message) {
    const chatContainer = document.querySelector('.chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chat-message';
    messageDiv.dataset.messageId = message.id;

    const isOwnMessage = message.sender_id == document.querySelector('[data-current-user-id]')?.dataset.currentUserId;
    if (isOwnMessage) {
        messageDiv.classList.add('own-message');
    }

    messageDiv.innerHTML = `
        <div class="message-sender">
            ${message.first_name} ${message.last_name}
        </div>
        <div class="message-content">
            ${escapeHtml(message.message)}
        </div>
        <div class="message-time">
            ${formatTime(message.created_at)}
        </div>
    `;

    chatContainer.appendChild(messageDiv);
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Star rating component
document.querySelectorAll('.star-rating').forEach(ratingElement => {
    const stars = ratingElement.querySelectorAll('.star');
    const input = ratingElement.querySelector('input[type="hidden"]');

    stars.forEach((star, index) => {
        star.addEventListener('click', function() {
            const value = index + 1;
            input.value = value;

            stars.forEach((s, i) => {
                if (i < value) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });

        star.addEventListener('mouseenter', function() {
            const value = index + 1;
            stars.forEach((s, i) => {
                if (i < value) {
                    s.classList.add('hover');
                } else {
                    s.classList.remove('hover');
                }
            });
        });

        ratingElement.addEventListener('mouseleave', function() {
            stars.forEach(s => s.classList.remove('hover'));
        });
    });
});
