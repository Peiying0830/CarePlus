(function () {
    'use strict';
    
    console.log('🤖 Guest Chatbot Loading...');
    
    let isChatOpen = false;
    
    // Get session info
    const sessionId = window.session_id_php || 'guest_session_' + Date.now();
    const patientId = window.patient_id_php || null;
    const userType = window.userType || 'guest';
    
    console.log('📋 Guest Chatbot Config:', {
        session_id: sessionId,
        patient_id: patientId,
        user_type: userType
    });
    
    // Create floating chat button
    const chatButton = document.createElement('button');
    chatButton.id = 'floating-chat-btn';
    chatButton.className = 'floating-chat-btn';
    chatButton.innerHTML = '💬';
    chatButton.setAttribute('aria-label', 'Open chat');
    chatButton.title = 'Chat with CarePlus Assistant';
    
    // Create floating chat window
    const chatWindow = document.createElement('div');
    chatWindow.id = 'floating-chat-window';
    chatWindow.className = 'floating-chat-window';
    chatWindow.innerHTML = `
        <div class="floating-chat-header">
            <div class="chat-header-content">
                <div class="chat-bot-avatar">🤖</div>
                <div class="chat-header-text">
                    <h3>CarePlus Assistant</h3>
                    <span class="chat-status">
                        <span class="status-dot"></span>
                        Online
                    </span>
                </div>
            </div>
            <button class="chat-close-btn" id="close-chat-btn" aria-label="Close chat">×</button>
        </div>
        
        <div class="floating-chatbox" id="floating-chatbox">
            <div class="message-wrapper bot welcome-message">
                <div class="message-avatar">🤖</div>
                <div class="message-content">
                    <div class="message-bubble">
                        👋 <strong>Hi! I'm your CarePlus assistant.</strong> How can I help you today?
                        <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                            <li>📅 Book appointments</li>
                            <li>🏥 Clinic information</li>
                            <li>👨‍⚕️ Find doctors</li>
                            <li>💡 Health tips</li>
                        </ul>
                    </div>
                    <div class="message-time">Just now</div>
                </div>
            </div>
            
            <div class="quick-actions">
                <button class="quick-action-btn" data-message="How do I book an appointment?">
                    📅 Book Appointment
                </button>
                <button class="quick-action-btn" data-message="What are your clinic hours?">
                    🕐 Clinic Hours
                </button>
                <button class="quick-action-btn" data-message="How can I find a doctor?">
                    👨‍⚕️ Find Doctor
                </button>
                <button class="quick-action-btn" data-message="How can I contact the clinic?">
                    📞 Contact
                </button><br>
            </div>
        </div>
        
        <div class="floating-chat-input-wrapper">
            <div class="floating-chat-input">
                <input 
                    type="text" 
                    id="floating-user-message" 
                    placeholder="Ask me anything..."
                    autocomplete="off"
                    aria-label="Type your message"
                >
                <button class="floating-send-btn" id="floating-send-btn" aria-label="Send message">
                    ➤
                </button>
            </div>
        </div>
        
        <div class="floating-chat-footer">
            <small>
                <a href="login.php" style="color: #667eea; text-decoration: underline;">Login</a> 
                for personalized assistance • 
                <span style="color: #ef4444;">⚠️ For emergencies, call 999</span>
            </small>
        </div>
    `;
    
    // Append to body when DOM is ready
    function initializeChatbot() {
        document.body.appendChild(chatButton);
        document.body.appendChild(chatWindow);
        
        console.log('✅ Guest chatbot UI created');
        
        // Toggle chat window
        chatButton.addEventListener('click', toggleChat);
        document.getElementById('close-chat-btn').addEventListener('click', toggleChat);
        
        // Setup input handlers
        const input = document.getElementById('floating-user-message');
        const sendBtn = document.getElementById('floating-send-btn');
        
        if (input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendFloatingMessage();
                }
            });
        }
        
        if (sendBtn) {
            sendBtn.addEventListener('click', sendFloatingMessage);
        }
        
        // Setup quick action buttons
        setupQuickActions();
        
        console.log('✅ Guest chatbot initialized');
    }
    
    function toggleChat() {
        isChatOpen = !isChatOpen;
        
        if (isChatOpen) {
            chatWindow.classList.add('active');
            chatButton.classList.add('hidden');
            setTimeout(() => {
                const input = document.getElementById('floating-user-message');
                if (input) input.focus();
            }, 300);
        } else {
            chatWindow.classList.remove('active');
            chatButton.classList.remove('hidden');
        }
    }
    
    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Scroll to bottom
    function scrollFloatingToBottom() {
        const chatbox = document.getElementById('floating-chatbox');
        if (chatbox) {
            chatbox.scrollTop = chatbox.scrollHeight;
        }
    }
    
    // Append user message
    function appendFloatingUserMessage(text) {
        const chatbox = document.getElementById('floating-chatbox');
        const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        const wrapper = document.createElement('div');
        wrapper.className = 'message-wrapper user';
        wrapper.innerHTML = `
            <div class="message-avatar">👤</div>
            <div class="message-content">
                <div class="message-bubble">${escapeHtml(text)}</div>
                <div class="message-time">${time}</div>
            </div>
        `;
        
        chatbox.appendChild(wrapper);
        scrollFloatingToBottom();
    }
    
    // Append bot message
    function appendFloatingBotMessage(text, messageClass = '') {
        const chatbox = document.getElementById('floating-chatbox');
        const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        const formattedText = escapeHtml(text).replace(/\n/g, '<br>');
        
        const wrapper = document.createElement('div');
        wrapper.className = `message-wrapper bot ${messageClass}`;
        wrapper.innerHTML = `
            <div class="message-avatar">🤖</div>
            <div class="message-content">
                <div class="message-bubble">${formattedText}</div>
                <div class="message-time">${time}</div>
            </div>
        `;
        
        chatbox.appendChild(wrapper);
        scrollFloatingToBottom();
    }
    
    // Show typing indicator
    function showFloatingTypingIndicator() {
        const chatbox = document.getElementById('floating-chatbox');
        const wrapper = document.createElement('div');
        wrapper.className = 'message-wrapper bot';
        wrapper.id = 'floating-typing-indicator';
        wrapper.innerHTML = `
            <div class="message-avatar">🤖</div>
            <div class="message-content">
                <div class="message-bubble">
                    <div class="typing-indicator">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
            </div>
        `;
        chatbox.appendChild(wrapper);
        scrollFloatingToBottom();
    }
    
    // Hide typing indicator
    function hideFloatingTypingIndicator() {
        const indicator = document.getElementById('floating-typing-indicator');
        if (indicator) indicator.remove();
    }
    
    // Send message function
    function sendFloatingMessage() {
        const input = document.getElementById('floating-user-message');
        const sendBtn = document.getElementById('floating-send-btn');
        const text = input.value.trim();
        
        if (!text) return;
        
        console.log('📤 Sending message:', text.substring(0, 50));
        
        input.disabled = true;
        sendBtn.disabled = true;
        
        appendFloatingUserMessage(text);
        input.value = '';
        showFloatingTypingIndicator();
        
        // Prepare request data
        const requestData = {
            message: text,
            session_id: sessionId,
            patient_id: patientId,
            user_type: userType
        };
        
        console.log('📋 Request:', {
            message_length: text.length,
            session_id: requestData.session_id.substring(0, 20) + '...',
            patient_id: requestData.patient_id,
            user_type: requestData.user_type
        });
        
        // Set timeout
        const requestTimeout = setTimeout(() => {
            hideFloatingTypingIndicator();
            appendFloatingBotMessage('⚠️ Request timed out. Please try again.', 'error-message');
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();
        }, 30000);
        
        // Send to API
        fetch('chatbot_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
        .then(res => {
            console.log('📥 Response status:', res.status);
            clearTimeout(requestTimeout);
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.text();
        })
        .then(text => {
            console.log('📄 Raw response:', text.substring(0, 200));
            hideFloatingTypingIndicator();
            
            if (!text || text.trim() === '') {
                throw new Error('Empty response from server');
            }
            
            let data;
            try {
                data = JSON.parse(text);
                console.log('✅ Parsed:', {
                    has_reply: !!data.reply,
                    is_restricted: data.is_restricted,
                    log_id: data.log_id,
                    scope_id: data.scope_id
                });
            } catch (e) {
                console.error('❌ JSON parse error:', e);
                throw new Error('Invalid response from server');
            }
            
            // Handle response
            if (data.error) {
                console.error('⚠️ API error:', data.error);
                
                let errorMsg = '⚠️ Sorry, I encountered an error. Please try again.';
                if (data.reply) errorMsg = data.reply;
                
                appendFloatingBotMessage(errorMsg, 'error-message');
            } 
            else if (data.reply) {
                console.log('💬 Reply received');
                
                if (data.is_restricted) {
                    appendFloatingBotMessage('⚠️ ' + data.reply, 'restricted-message');
                } else {
                    appendFloatingBotMessage(data.reply);
                }
            } 
            else {
                appendFloatingBotMessage('⚠️ Sorry, I couldn\'t generate a response.', 'error-message');
            }
        })
        .catch(error => {
            clearTimeout(requestTimeout);
            hideFloatingTypingIndicator();
            
            console.error('❌ Error:', error);
            
            let errorMsg = '⚠️ Connection error. Please check your internet.';
            if (error.message.includes('timeout')) {
                errorMsg = '⚠️ Request timed out. Please try again.';
            }
            
            appendFloatingBotMessage(errorMsg, 'error-message');
        })
        .finally(() => {
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();
        });
    }
    
    // Quick action buttons
    function setupQuickActions() {
        const quickButtons = document.querySelectorAll('.quick-action-btn');
        quickButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const message = this.getAttribute('data-message');
                if (message) {
                    document.getElementById('floating-user-message').value = message;
                    sendFloatingMessage();
                }
            });
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeChatbot);
    } else {
        initializeChatbot();
    }
    
})();