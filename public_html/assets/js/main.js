/**
 * TDT3E Student Dashboard - Main JavaScript
 * 
 * Features:
 * - Chatbot widget initialization
 * - Message handling
 * - Notification system
 * - Interactive elements
 */

// ===== CHATBOT WIDGET =====

class ChatbotWidget {
    constructor() {
        this.isOpen = false;
        this.messages = [];
        this.init();
    }
    
    /**
     * Initialize chatbot widget
     */
    init() {
        this.createWidget();
        this.attachEventListeners();
    }
    
    /**
     * Create chatbot HTML structure
     */
    createWidget() {
        // Create floating button
        const button = document.createElement('button');
        button.id = 'chatbot-button';
        button.className = 'chatbot-floating-button';
        button.innerHTML = '💬';
        button.title = 'Open Chat';
        
        // Create chatbot container
        const container = document.createElement('div');
        container.id = 'chatbot-container';
        container.className = 'chatbot-container hidden';
        
        container.innerHTML = `
            <div class="chatbot-header">
                <h3>TDT3E Assistant</h3>
                <button class="chatbot-close" title="Close">✕</button>
            </div>
            <div class="chatbot-messages" id="chatbot-messages">
                <div class="chatbot-message bot-message">
                    <p>👋 Hello! I'm your TDT3E Assistant. How can I help you today?</p>
                </div>
            </div>
            <div class="chatbot-input-area">
                <input 
                    type="text" 
                    id="chatbot-input" 
                    class="chatbot-input" 
                    placeholder="Type your message..."
                    autocomplete="off"
                >
                <button class="chatbot-send" title="Send">📤</button>
            </div>
        `;
        
        // Add to page
        document.body.appendChild(button);
        document.body.appendChild(container);
        
        // Add styles
        this.addStyles();
    }
    
    /**
     * Add chatbot CSS styles
     */
    addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            /* Floating Button */
            .chatbot-floating-button {
                position: fixed;
                bottom: 24px;
                right: 24px;
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
                color: white;
                border: none;
                font-size: 28px;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                transition: all 0.3s ease;
                z-index: 999;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .chatbot-floating-button:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 16px rgba(0, 212, 255, 0.3);
            }
            
            .chatbot-floating-button:active {
                transform: scale(0.95);
            }
            
            /* Chat Container */
            .chatbot-container {
                position: fixed;
                bottom: 100px;
                right: 24px;
                width: 380px;
                max-height: 600px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                display: flex;
                flex-direction: column;
                z-index: 999;
                animation: slideUp 0.3s ease;
            }
            
            .chatbot-container.hidden {
                display: none;
            }
            
            @keyframes slideUp {
                from {
                    transform: translateY(20px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
            
            /* Chat Header */
            .chatbot-header {
                background: linear-gradient(135deg, #0f1419 0%, #1a2332 100%);
                color: white;
                padding: 16px;
                border-radius: 12px 12px 0 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 3px solid #00d4ff;
            }
            
            .chatbot-header h3 {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
            }
            
            .chatbot-close {
                background: none;
                border: none;
                color: white;
                font-size: 20px;
                cursor: pointer;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
            }
            
            .chatbot-close:hover {
                background-color: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
            }
            
            /* Messages Area */
            .chatbot-messages {
                flex: 1;
                overflow-y: auto;
                padding: 16px;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            
            .chatbot-message {
                display: flex;
                animation: fadeIn 0.3s ease;
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .chatbot-message p {
                margin: 0;
                padding: 10px 14px;
                border-radius: 8px;
                font-size: 14px;
                max-width: 80%;
                word-wrap: break-word;
            }
            
            /* Bot Message */
            .bot-message {
                justify-content: flex-start;
            }
            
            .bot-message p {
                background-color: #f0f0f0;
                color: #333;
            }
            
            /* User Message */
            .user-message {
                justify-content: flex-end;
            }
            
            .user-message p {
                background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
                color: white;
            }
            
            /* Input Area */
            .chatbot-input-area {
                display: flex;
                gap: 8px;
                padding: 12px;
                border-top: 1px solid #eee;
                background: white;
                border-radius: 0 0 12px 12px;
            }
            
            .chatbot-input {
                flex: 1;
                padding: 10px 12px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-family: inherit;
                font-size: 14px;
                transition: all 0.2s ease;
            }
            
            .chatbot-input:focus {
                outline: none;
                border-color: #00d4ff;
                box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
            }
            
            .chatbot-send {
                background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
                color: white;
                border: none;
                border-radius: 6px;
                width: 40px;
                height: 40px;
                cursor: pointer;
                font-size: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
            }
            
            .chatbot-send:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 212, 255, 0.3);
            }
            
            .chatbot-send:active {
                transform: translateY(0);
            }
            
            /* Responsive */
            @media (max-width: 480px) {
                .chatbot-container {
                    width: 100%;
                    height: 100%;
                    max-height: 100%;
                    bottom: 0;
                    right: 0;
                    left: 0;
                    border-radius: 0;
                }
                
                .chatbot-floating-button {
                    bottom: 16px;
                    right: 16px;
                }
                
                .chatbot-message p {
                    max-width: 100%;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    /**
     * Attach event listeners
     */
    attachEventListeners() {
        const button = document.getElementById('chatbot-button');
        const closeBtn = document.querySelector('.chatbot-close');
        const sendBtn = document.querySelector('.chatbot-send');
        const input = document.getElementById('chatbot-input');
        
        // Toggle chat window
        button.addEventListener('click', () => this.toggleChat());
        closeBtn.addEventListener('click', () => this.closeChat());
        
        // Send message on button click
        sendBtn.addEventListener('click', () => this.sendMessage());
        
        // Send message on Enter key
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });
    }
    
    /**
     * Toggle chat window
     */
    toggleChat() {
        if (this.isOpen) {
            this.closeChat();
        } else {
            this.openChat();
        }
    }
    
    /**
     * Open chat window
     */
    openChat() {
        const container = document.getElementById('chatbot-container');
        container.classList.remove('hidden');
        this.isOpen = true;
        document.getElementById('chatbot-input').focus();
    }
    
    /**
     * Close chat window
     */
    closeChat() {
        const container = document.getElementById('chatbot-container');
        container.classList.add('hidden');
        this.isOpen = false;
    }
    
    /**
     * Send message
     */
    sendMessage() {
        const input = document.getElementById('chatbot-input');
        const message = input.value.trim();
        
        if (!message) return;
        
        // Add user message
        this.addMessage(message, 'user');
        input.value = '';
        
        // Simulate bot response
        setTimeout(() => {
            const response = this.getBotResponse(message);
            this.addMessage(response, 'bot');
        }, 500);
    }
    
    /**
     * Add message to chat
     */
    addMessage(text, sender) {
        const messagesContainer = document.getElementById('chatbot-messages');
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${sender}-message`;
        
        const p = document.createElement('p');
        p.textContent = text;
        
        messageDiv.appendChild(p);
        messagesContainer.appendChild(messageDiv);
        
        // Scroll to bottom
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Store message
        this.messages.push({
            text: text,
            sender: sender,
            timestamp: new Date()
        });
    }
    
    /**
     * Get bot response (dummy implementation)
     * TODO: Connect to actual chatbot API/backend
     */
    getBotResponse(userMessage) {
        const lower = userMessage.toLowerCase();
        
        // Simple keyword matching
        if (lower.includes('hello') || lower.includes('hi')) {
            return '👋 Hi there! How can I assist you with your studies?';
        }
        
        if (lower.includes('grade') || lower.includes('score')) {
            return '📊 You can view your grades in the Grades section. Would you like help with anything specific?';
        }
        
        if (lower.includes('task') || lower.includes('assignment')) {
            return '📋 Check out the Tasks section to manage your assignments and deadlines!';
        }
        
        if (lower.includes('blog') || lower.includes('post')) {
            return '📝 Visit the Blog page to write and share your thoughts with the community!';
        }
        
        if (lower.includes('file') || lower.includes('drive') || lower.includes('upload')) {
            return '📁 You can upload and manage files in the Files section. Google Drive integration is coming soon!';
        }
        
        if (lower.includes('help') || lower.includes('support')) {
            return '💬 I\'m here to help! Ask me about grades, tasks, blog, files, or anything else!';
        }
        
        if (lower.includes('thank')) {
            return '😊 You\'re welcome! Anything else I can help with?';
        }
        
        // Default response
        return '🤔 I\'m not sure about that. Try asking about grades, tasks, blog, or files! Or check out the help section.';
    }
}

// ===== INITIALIZE ON PAGE LOAD =====

document.addEventListener('DOMContentLoaded', function() {
    // Initialize chatbot widget
    const chatbot = new ChatbotWidget();
    
    // Add any other initialization code here
    console.log('Dashboard initialized successfully');
});

// ===== UTILITY FUNCTIONS =====

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 6px;
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            animation: slideInRight 0.3s ease;
        }
        
        .notification-success {
            border-left: 4px solid #10b981;
            color: #166534;
        }
        
        .notification-error {
            border-left: 4px solid #ef4444;
            color: #7f1d1d;
        }
        
        .notification-info {
            border-left: 4px solid #3b82f6;
            color: #0c2d6b;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    
    document.head.appendChild(style);
    document.body.appendChild(notification);
    
    // Remove after 4 seconds
    setTimeout(() => {
        notification.remove();
    }, 4000);
}

/**
 * Format date to readable format
 */
function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}
