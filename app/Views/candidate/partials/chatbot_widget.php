<!-- ─── Candidate AI Assistant Widget ─── -->
<style>
.hm-candidate-chat-fab {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--candidate-accent, var(--primary, #1FB7B5));
    color: #fff;
    border: none;
    box-shadow: 0 6px 24px rgba(31, 183, 181, 0.25);
    cursor: pointer;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}
.hm-candidate-chat-fab:hover { transform: scale(1.05); }
.hm-candidate-chat-fab:focus,
.hm-candidate-chat-fab:focus-visible,
.hm-candidate-chat-send:focus,
.hm-candidate-chat-send:focus-visible,
.hm-candidate-chat-header-close:focus,
.hm-candidate-chat-header-close:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(31, 183, 181, 0.18);
}
.hm-candidate-chat-widget {
    position: fixed;
    bottom: 92px;
    right: 24px;
    width: 380px;
    height: 520px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.14);
    z-index: 10000;
    display: none;
    flex-direction: column;
    overflow: hidden;
}
.hm-candidate-chat-widget.open { display: flex; }
.hm-candidate-chat-header {
    padding: 14px 16px;
    background: var(--candidate-accent, var(--primary, #1FB7B5));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.hm-candidate-chat-header strong {
    display: block;
    font-size: 15px;
    line-height: 1.2;
}
.hm-candidate-chat-header small {
    display: block;
    margin-top: 2px;
    opacity: 0.9;
    font-size: 12px;
}
.hm-candidate-chat-header-close {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    transition: background 0.15s ease;
}
.hm-candidate-chat-header-close:hover {
    background: rgba(255, 255, 255, 0.28);
}
.hm-candidate-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f4f8ff;
}
.hm-candidate-chat-bubble {
    display: inline-block;
    max-width: 85%;
    padding: 10px 14px;
    border-radius: 14px;
    margin-bottom: 10px;
    font-size: 13.5px;
    line-height: 1.5;
    white-space: pre-wrap;
}
.hm-candidate-chat-bubble.bot { background: #eef4ff; color: #14213d; }
.hm-candidate-chat-bubble.user { background: var(--candidate-accent, var(--primary, #1FB7B5)); color: #fff; margin-left: auto; }
.hm-candidate-chat-suggestions { display: flex; flex-wrap: wrap; gap: 6px; padding: 0 16px 10px; }
.hm-candidate-chat-suggestions button { border: 1px solid rgba(31, 183, 181, 0.28); background: rgba(31, 183, 181, 0.08); color: var(--candidate-accent-dark, var(--primary-dark, #0D8A90)); padding: 6px 14px; border-radius: 999px; font-size: 12px; cursor: pointer; transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease; }
.hm-candidate-chat-suggestions button:hover { background: rgba(31, 183, 181, 0.16); border-color: rgba(31, 183, 181, 0.42); color: var(--candidate-accent-dark, var(--primary-dark, #0D8A90)); }
.hm-candidate-chat-input-wrap { display: flex; gap: 8px; padding: 10px 14px; border-top: 1px solid #dbeafe; background: #fff; }
.hm-candidate-chat-input { flex: 1; border: 1px solid #dbeafe; border-radius: 999px; padding: 10px 14px; font-size: 14px; color: #0f172a; }
.hm-candidate-chat-input:focus { outline: none; border-color: var(--candidate-accent, var(--primary, #1FB7B5)); box-shadow: 0 0 0 3px rgba(31, 183, 181, 0.12); }
.hm-candidate-chat-send { border: none; background: var(--candidate-accent, var(--primary, #1FB7B5)); color: #fff; width: 38px; height: 38px; border-radius: 50%; cursor: pointer; }
@media (max-width: 480px) {
    .hm-candidate-chat-widget { right: 12px; left: 12px; width: auto; height: 70vh; bottom: 80px; }
    .hm-candidate-chat-fab { right: 16px; bottom: 16px; }
}
</style>

<button class="hm-candidate-chat-fab" id="hmCandidateChatFab" aria-label="Open assistant" title="HireMate Candidate Assistant">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
    </svg>
</button>

<div class="hm-candidate-chat-widget" id="hmCandidateChatWidget">
    <div class="hm-candidate-chat-header">
        <div>
            <strong>HireMate Assistant</strong>
            <small>Ask about jobs, applications, and interviews</small>
        </div>
        <button type="button" class="hm-candidate-chat-header-close" id="hmCandidateChatClose" aria-label="Close">×</button>
    </div>
    <div class="hm-candidate-chat-messages" id="hmCandidateChatMessages">
        <div class="hm-candidate-chat-bubble bot">Hi! I can help you review jobs, applications, saved roles, interviews, and your profile. Ask me anything.</div>
    </div>
    <div class="hm-candidate-chat-suggestions" id="hmCandidateChatSuggestions"></div>
    <div class="hm-candidate-chat-input-wrap">
        <input type="text" class="hm-candidate-chat-input" id="hmCandidateChatInput" placeholder="Ask about your job portal data..." autocomplete="off">
        <button type="button" class="hm-candidate-chat-send" id="hmCandidateChatSend">➤</button>
    </div>
</div>

<script>
(function () {
    const fab = document.getElementById('hmCandidateChatFab');
    const widget = document.getElementById('hmCandidateChatWidget');
    const close = document.getElementById('hmCandidateChatClose');
    const messages = document.getElementById('hmCandidateChatMessages');
    const input = document.getElementById('hmCandidateChatInput');
    const send = document.getElementById('hmCandidateChatSend');
    const suggestions = document.getElementById('hmCandidateChatSuggestions');

    if (!fab || !widget || !messages || !input || !send) return;

    const baseUrl = (document.querySelector('meta[name="base-url"]')?.getAttribute('content') || window.location.origin + '/ai-job-portal/public').replace(/\/+$/, '');
    const askUrl = baseUrl + '/candidate/chatbot/ask';
    const suggUrl = baseUrl + '/candidate/chatbot/suggestions';

    function addMessage(text, type) {
        const row = document.createElement('div');
        row.className = 'hm-candidate-chat-bubble ' + type;
        row.textContent = text;
        messages.appendChild(row);
        messages.scrollTop = messages.scrollHeight;
    }

    function setLoading(isLoading) {
        send.disabled = isLoading;
        input.disabled = isLoading;
        send.textContent = isLoading ? '…' : '➤';
    }

    function loadSuggestions() {
        fetch(suggUrl, { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data || !Array.isArray(data.suggestions)) return;
                suggestions.innerHTML = '';
                data.suggestions.forEach(text => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = text;
                    btn.addEventListener('click', () => {
                        input.value = text;
                        sendMessage();
                    });
                    suggestions.appendChild(btn);
                });
            }).catch(() => {});
    }

    function sendMessage() {
        const question = input.value.trim();
        if (!question) return;
        addMessage(question, 'user');
        input.value = '';
        setLoading(true);
        addMessage('Thinking…', 'bot');

        fetch(askUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'Accept': 'application/json' },
            body: new URLSearchParams({ question: question }),
            credentials: 'same-origin'
        })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                const lastBubble = messages.querySelector('.hm-candidate-chat-bubble.bot:last-of-type');
                if (lastBubble) {
                    lastBubble.textContent = data && data.answer ? data.answer : 'I could not answer that right now.';
                }
            })
            .catch(() => {
                const lastBubble = messages.querySelector('.hm-candidate-chat-bubble.bot:last-of-type');
                if (lastBubble) {
                    lastBubble.textContent = 'The assistant is temporarily unavailable.';
                }
            })
            .finally(() => {
                setLoading(false);
                input.focus();
            });
    }

    fab.addEventListener('click', function () {
        widget.classList.add('open');
        fab.style.display = 'none';
        input.focus();
        if (!suggestions.dataset.loaded) {
            suggestions.dataset.loaded = '1';
            loadSuggestions();
        }
    });
    close.addEventListener('click', function () {
        widget.classList.remove('open');
        fab.style.display = 'flex';
    });
    send.addEventListener('click', sendMessage);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });
})();
</script>
