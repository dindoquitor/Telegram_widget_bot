document.addEventListener('DOMContentLoaded', () => {
    const chatWindow = document.getElementById('tw-chat-window');
    const toggleBtn = document.getElementById('tw-toggle-btn');
    const closeBtn = document.getElementById('tw-close-btn');
    const messagesContainer = document.getElementById('tw-messages');
    const inputField = document.getElementById('tw-input');
    const sendBtn = document.getElementById('tw-send-btn');
    const statusText = document.querySelector('.tw-status');

    let sessionId = localStorage.getItem('tw_session_id') || '';
    let csrfToken = '';
    let isHumanMode = false;
    let isPolling = false;

    // Initialization
    async function init() {
        try {
            const res = await fetch(`api.php?action=init&session_id=${sessionId}`);
            const data = await res.json();
            
            csrfToken = data.csrf_token;
            sessionId = data.session_id;
            localStorage.setItem('tw_session_id', sessionId);
            isHumanMode = data.human_mode;
            
            updateStatusText();

            // Load history
            if (data.messages && data.messages.length > 0) {
                messagesContainer.innerHTML = ''; // clear default greeting
                data.messages.forEach(msg => {
                    appendMessage(msg.role, msg.content);
                });
            }

            // Start polling if human mode
            if (isHumanMode) startPolling();

        } catch (err) {
            console.error('Failed to init widget', err);
        }
    }

    // Toggle logic
    toggleBtn.addEventListener('click', () => {
        chatWindow.classList.remove('hidden');
        setTimeout(() => {
            chatWindow.classList.remove('translate-y-4', 'opacity-0');
            toggleBtn.classList.add('scale-0');
        }, 10);
    });

    closeBtn.addEventListener('click', () => {
        chatWindow.classList.add('translate-y-4', 'opacity-0');
        toggleBtn.classList.remove('scale-0');
        setTimeout(() => {
            chatWindow.classList.add('hidden');
        }, 300);
    });

    // Send Message
    async function sendMessage() {
        const text = inputField.value.trim();
        if (!text) return;

        inputField.value = '';
        appendMessage('user', text);

        const formData = new URLSearchParams();
        formData.append('session_id', sessionId);
        formData.append('message', text);

        // typing indicator
        const typingId = appendTypingIndicator();

        try {
            const res = await fetch('api.php?action=send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': csrfToken
                },
                body: formData.toString()
            });
            const data = await res.json();
            
            document.getElementById(typingId)?.remove();

            if (data.reply) {
                appendMessage(data.human_mode ? 'system' : 'ai', data.reply);
            }

            if (data.human_mode && !isHumanMode) {
                isHumanMode = true;
                updateStatusText();
                startPolling();
            }

        } catch (err) {
            console.error(err);
            document.getElementById(typingId)?.remove();
            appendMessage('system', 'Error connecting to server.');
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    inputField.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    // Polling
    function startPolling() {
        if (isPolling) return;
        isPolling = true;
        
        setInterval(async () => {
            if (!isHumanMode) return;
            try {
                const res = await fetch(`api.php?action=poll&session_id=${sessionId}`);
                const data = await res.json();
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => appendMessage('human', msg.content));
                }
            } catch (err) {
                console.error('Polling error', err);
            }
        }, 3000); // poll every 3 seconds
    }

    // UI Helpers
    function appendMessage(role, text) {
        const div = document.createElement('div');
        div.className = `px-4 py-2 text-sm shadow-sm max-w-[85%] ${
            role === 'user' 
                ? 'self-end bg-blue-600 text-white rounded-2xl rounded-tr-none' 
                : (role === 'system' 
                    ? 'self-center bg-gray-200 text-gray-500 rounded-lg text-xs' 
                    : 'self-start bg-white border border-gray-100 text-gray-800 rounded-2xl rounded-tl-none')
        }`;
        div.textContent = text;
        messagesContainer.appendChild(div);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function appendTypingIndicator() {
        const id = 'typing-' + Date.now();
        const div = document.createElement('div');
        div.id = id;
        div.className = 'self-start bg-white border border-gray-100 rounded-2xl rounded-tl-none px-4 py-3 shadow-sm flex gap-1';
        div.innerHTML = `
            <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></div>
            <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
            <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
        `;
        messagesContainer.appendChild(div);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        return id;
    }

    function updateStatusText() {
        statusText.textContent = isHumanMode ? 'Human Agent' : 'AI Assistant';
        statusText.className = isHumanMode ? 'text-xs text-green-300 tw-status font-semibold' : 'text-xs text-blue-100 tw-status';
    }

    init();
});
