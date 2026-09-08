/**
 * NexaWork - Floating AI Assistant & Smart Helper Widget
 */
document.addEventListener('DOMContentLoaded', function () {
    // Inject Floating AI Chat Widget HTML if not existing
    if (!document.getElementById('nexa-ai-widget')) {
        const widgetHtml = `
            <div id="nexa-ai-widget" class="nexa-ai-container">
                <button id="nexa-ai-toggle" class="nexa-ai-fab shadow-lg" title="Ask NexaAI Assistant">
                    <i class="fas fa-robot fs-4 text-white"></i>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">AI Ready</span>
                    </span>
                </button>

                <div id="nexa-ai-box" class="nexa-ai-card shadow-lg d-none">
                    <div class="nexa-ai-header d-flex align-items-center justify-content-between p-3 bg-gradient text-white">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-white bg-opacity-25 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="fas fa-brain text-white"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">NexaAI Assistant</h6>
                                <small class="text-white-50" style="font-size: 11px;">Smart Talent & Matching Engine</small>
                            </div>
                        </div>
                        <button id="nexa-ai-close" type="button" class="btn-close btn-close-white small" aria-label="Close"></button>
                    </div>

                    <div id="nexa-ai-messages" class="nexa-ai-body p-3 overflow-auto">
                        <div class="ai-msg ai-bot-msg mb-3">
                            <div class="p-3 rounded-3 bg-light text-dark small shadow-sm">
                                👋 Hi! I'm <strong>NexaAI</strong>. How can I help you today?
                                <div class="mt-2 d-flex flex-wrap gap-1">
                                    <button class="btn btn-xs btn-outline-primary ai-quick-btn" data-query="Find top developers">👨‍💻 Top Freelancers</button>
                                    <button class="btn btn-xs btn-outline-success ai-quick-btn" data-query="Browse open projects">📁 Open Projects</button>
                                    <button class="btn btn-xs btn-outline-info ai-quick-btn" data-query="How does milestone escrow work?">🛡️ Escrow Rules</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="nexa-ai-footer p-2 bg-white border-top">
                        <form id="nexa-ai-form" class="d-flex gap-2">
                            <input type="text" id="nexa-ai-input" class="form-control form-control-sm" placeholder="Ask NexaAI anything..." required autocomplete="off">
                            <button type="submit" class="btn btn-sm btn-primary px-3"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', widgetHtml);
    }

    const toggleBtn = document.getElementById('nexa-ai-toggle');
    const closeBtn = document.getElementById('nexa-ai-close');
    const aiBox = document.getElementById('nexa-ai-box');
    const aiForm = document.getElementById('nexa-ai-form');
    const aiInput = document.getElementById('nexa-ai-input');
    const aiMessages = document.getElementById('nexa-ai-messages');

    if (toggleBtn && aiBox) {
        toggleBtn.addEventListener('click', () => {
            aiBox.classList.toggle('d-none');
            if (!aiBox.classList.contains('d-none')) {
                aiInput.focus();
            }
        });

        closeBtn.addEventListener('click', () => {
            aiBox.classList.add('d-none');
        });

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('ai-quick-btn')) {
                const q = e.target.getAttribute('data-query');
                if (q) {
                    aiInput.value = q;
                    aiForm.dispatchEvent(new Event('submit'));
                }
            }
        });

        aiForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const text = aiInput.value.trim();
            if (!text) return;

            // Render User Message
            appendMessage('user', text);
            aiInput.value = '';

            // Render Loading state
            const loadingId = appendLoading();

            // Fetch AJAX
            const formData = new FormData();
            formData.append('action', 'chat_assistant');
            formData.append('query', text);

            fetch(window.APP_URL ? window.APP_URL + '/api/ai_assistant.php' : '/PHP/freelancehub/api/ai_assistant.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                removeLoading(loadingId);
                if (data.success) {
                    appendMessage('bot', data.response);
                } else {
                    appendMessage('bot', '⚠️ Sorry, I encountered an issue processing your request.');
                }
            })
            .catch(err => {
                removeLoading(loadingId);
                appendMessage('bot', '⚠️ Unable to connect to AI server.');
            });
        });
    }

    function appendMessage(sender, text) {
        const isBot = sender === 'bot';
        const formattedText = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                                  .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" class="fw-bold text-decoration-underline">$1</a>')
                                  .replace(/\n/g, '<br>');

        const msgHtml = `
            <div class="ai-msg ${isBot ? 'ai-bot-msg' : 'ai-user-msg'} mb-3 ${isBot ? '' : 'text-end'}">
                <div class="p-3 rounded-3 ${isBot ? 'bg-light text-dark shadow-sm' : 'bg-primary text-white'} d-inline-block text-start small max-w-80">
                    ${formattedText}
                </div>
            </div>
        `;
        aiMessages.insertAdjacentHTML('beforeend', msgHtml);
        aiMessages.scrollTop = aiMessages.scrollHeight;
    }

    function appendLoading() {
        const id = 'loading-' + Date.now();
        const html = `
            <div id="${id}" class="ai-msg ai-bot-msg mb-3">
                <div class="p-3 rounded-3 bg-light text-muted small shadow-sm d-inline-block">
                    <i class="fas fa-spinner fa-spin me-2 text-primary"></i> NexaAI is thinking...
                </div>
            </div>
        `;
        aiMessages.insertAdjacentHTML('beforeend', html);
        aiMessages.scrollTop = aiMessages.scrollHeight;
        return id;
    }

    function removeLoading(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }
});
