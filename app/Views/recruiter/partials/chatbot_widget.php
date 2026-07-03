<!-- ─── Recruiter AI Chatbot Widget ─── -->
<style>
/* ═══════════════════════════════════════
   CHATBOT FAB + WIDGET
════════════════════════════════════════ */
.hm-chat-fab {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--hm-primary, var(--primary, #1FB7B5));
    color: #fff;
    border: none;
    box-shadow: none;
    cursor: pointer;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    transition: transform 0.2s, background 0.2s;
}
.hm-chat-fab:hover {
    background: var(--hm-primary-dark, var(--primary-dark, #0D8A90));
    transform: scale(1.04);
}
.hm-chat-fab:focus,
.hm-chat-fab:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(31, 183, 181, 0.18);
}

/* Widget panel */
.hm-chat-widget {
    position: fixed;
    bottom: 90px;
    right: 24px;
    width: 380px;
    height: min(720px, calc(100vh - 118px));
    min-height: 560px;
    background: #fff;
    border: 1px solid #D9ECE5;
    border-radius: 16px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.12);
    z-index: 10000;
    display: none;
    flex-direction: column;
    overflow: hidden;
    animation: hmChatSlideUp 0.25s ease;
}
@keyframes hmChatSlideUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}

.hm-chat-widget.open { display: flex; }

/* Header */
.hm-chat-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 100%);
    color: #fff;
    flex-shrink: 0;
}
.hm-chat-header-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}
.hm-chat-header-title {
    flex: 1;
    font-weight: 700;
    font-size: 15px;
}
.hm-chat-header-close {
    background: none;
    border: none;
    color: rgba(255,255,255,0.8);
    font-size: 20px;
    cursor: pointer;
    padding: 0 2px;
    line-height: 1;
}
.hm-chat-header-close:hover { color: #fff; }
.hm-chat-header-close:focus,
.hm-chat-header-close:focus-visible {
    outline: none;
    box-shadow: none;
}

/* Messages area */
.hm-chat-messages {
    flex: 1 1 auto;
    overflow-y: auto !important;
    overflow-x: hidden;
    padding: 16px;
    min-height: 0;
    background: #F8FCFA;
}
.hm-chat-messages::-webkit-scrollbar { width: 4px; }
.hm-chat-messages::-webkit-scrollbar-thumb { background: #D9ECE5; border-radius: 4px; }

/* Message bubbles */
.hm-msg {
    margin-bottom: 12px;
    max-width: 85%;
    animation: hmMsgIn 0.2s ease;
}
@keyframes hmMsgIn {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.hm-msg.bot {
    margin-right: auto;
}
.hm-msg.user {
    margin-left: auto;
    text-align: right;
}
.hm-msg-bubble {
    display: inline-block;
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 13.5px;
    line-height: 1.5;
    text-align: left;
    white-space: pre-wrap;
    word-wrap: break-word;
}
.hm-msg.bot .hm-msg-bubble {
    background: #E8F9F8;
    color: #16212B;
    border-bottom-left-radius: 4px;
}
.hm-msg.user .hm-msg-bubble {
    background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 100%);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.hm-msg-time {
    font-size: 10px;
    color: #94A3B8;
    margin-top: 3px;
    padding: 0 4px;
}

/* Quick suggestions */
.hm-chat-suggestions {
    padding: 0 0 4px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    flex-shrink: 0;
}
.hm-chat-suggestions button {
    background: #E8F9F8;
    border: 1px solid #D9ECE5;
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 600;
    color: #0D8A90;
    cursor: pointer;
    transition: background 0.15s;
    white-space: nowrap;
}
.hm-chat-suggestions button:hover {
    background: #1FB7B5;
    color: #fff;
    border-color: #1FB7B5;
}

/* Input area */
.hm-chat-input-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-top: 1px solid #D9ECE5;
    background: #fff;
    flex-shrink: 0;
}
.hm-chat-input {
    flex: 1;
    border: 1px solid #D9ECE5;
    border-radius: 24px;
    padding: 9px 16px;
    font-size: 13.5px;
    outline: none;
    background: #F8FCFA;
    transition: border-color 0.15s;
}
.hm-chat-input:focus {
    border-color: #1FB7B5;
}
.hm-chat-input::placeholder { color: #94A3B8; }

.hm-chat-send,
.hm-chat-voice {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 100%);
    color: #fff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    transition: transform 0.15s;
}
.hm-chat-send:hover { transform: scale(1.06); }
.hm-chat-send:focus,
.hm-chat-send:focus-visible,
.hm-chat-voice:focus,
.hm-chat-voice:focus-visible {
    outline: none;
    box-shadow: none;
}
.hm-chat-voice {
    background: #E8F9F8;
    color: #0D8A90;
    border: 1px solid #D9ECE5;
}
.hm-chat-voice:hover { transform: scale(1.06); }
.hm-chat-voice.is-listening,
.hm-chat-voice.is-speaking {
    background: #ef4444;
    border-color: #ef4444;
    color: #fff;
    animation: hmRecruiterVoicePulse 1s ease-in-out infinite;
}
.hm-chat-send:disabled,
.hm-chat-voice:disabled { opacity: 0.4; cursor: default; transform: none; }
@keyframes hmRecruiterVoicePulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.08); }
}

/* Typing indicator */
.hm-typing {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 6px 0;
}
.hm-typing span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #94A3B8;
    animation: hmTyping 1.2s infinite;
}
.hm-typing span:nth-child(2) { animation-delay: 0.2s; }
.hm-typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes hmTyping {
    0%, 60%, 100% { opacity: 0.3; }
    30% { opacity: 1; }
}

/* Dark mode overrides */
body.dark .hm-chat-widget {
    background: #000;
    border-color: #23343A;
}
body.dark .hm-chat-messages {
    background: #000 !important;
}
body.dark .hm-msg.bot .hm-msg-bubble {
    background: #162327;
    color: #F8FAFC;
}
body.dark .hm-msg-time { color: #7A8B96; }
body.dark .hm-chat-input-wrap {
    background: #000;
    border-top-color: #23343A;
}
body.dark .hm-chat-input {
    background: #0A0D0F;
    border-color: #23343A;
    color: #F8FAFC;
}
body.dark .hm-chat-input:focus { border-color: #1FB7B5; }
body.dark .hm-chat-suggestions button {
    background: #162327;
    border-color: #23343A;
    color: #1FB7B5;
}
body.dark .hm-chat-suggestions button:hover {
    background: #1FB7B5;
    color: #fff;
}
body.dark .hm-chat-voice {
    background: #162327;
    border-color: #23343A;
    color: #1FB7B5;
}
body.dark .hm-chat-voice.is-listening,
body.dark .hm-chat-voice.is-speaking {
    background: #ef4444;
    border-color: #ef4444;
    color: #fff;
}

@media (max-width: 480px) {
    .hm-chat-widget {
        right: 12px;
        left: 12px;
        bottom: 80px;
        width: auto;
        height: calc(100vh - 104px);
        min-height: 0;
    }
    .hm-chat-fab {
        right: 16px;
        bottom: 16px;
    }
}
</style>

<button class="hm-chat-fab" id="hmChatFab" aria-label="Open AI assistant">
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="14" rx="2"/>
        <line x1="9" y1="10" x2="15" y2="10"/>
        <line x1="12" y1="7" x2="12" y2="13"/>
        <path d="M9 17l-3 4h12l-3-4"/>
    </svg>
</button>

<div class="hm-chat-widget" id="hmChatWidget">
    <div class="hm-chat-header">
        <div class="hm-chat-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="14" rx="2"/>
                <line x1="9" y1="10" x2="15" y2="10"/>
                <line x1="12" y1="7" x2="12" y2="13"/>
                <path d="M9 17l-3 4h12l-3-4"/>
            </svg>
        </div>
        <div class="hm-chat-header-title">HireMate AI</div>
        <button class="hm-chat-header-close" id="hmChatClose">&times;</button>
    </div>

    <div class="hm-chat-messages" id="hmChatMessages">
        <div class="hm-msg bot">
            <div class="hm-msg-bubble">
                Hi! I'm HireMate, your AI recruitment assistant. Ask me anything about your jobs, applications, candidates, or interviews.
            </div>
            <div class="hm-msg-time">Just now</div>
        </div>
        <div class="hm-chat-suggestions" id="hmChatSuggestions"></div>
    </div>

    <div class="hm-chat-input-wrap">
        <input type="text" class="hm-chat-input" id="hmChatInput"
               placeholder="Ask about your hiring data..." autocomplete="off">
        <button class="hm-chat-voice" id="hmChatVoice" aria-label="Start voice chat" title="Start voice chat" type="button">
            <svg class="hm-chat-voice-mic" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
                <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                <line x1="12" y1="19" x2="12" y2="22"/>
            </svg>
            <svg class="hm-chat-voice-stop" style="display:none" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <rect x="6" y="6" width="12" height="12" rx="2"/>
            </svg>
        </button>
        <button class="hm-chat-send" id="hmChatSend" aria-label="Send">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
        </button>
    </div>
</div>

<script>
(function () {
    'use strict';

    var fab     = document.getElementById('hmChatFab');
    var widget  = document.getElementById('hmChatWidget');
    var close   = document.getElementById('hmChatClose');
    var msgBox  = document.getElementById('hmChatMessages');
    var input   = document.getElementById('hmChatInput');
    var voiceBtn = document.getElementById('hmChatVoice');
    var sendBtn = document.getElementById('hmChatSend');
    var suggBox = document.getElementById('hmChatSuggestions');

    // Use relative URLs — pick them from the page meta or build from window.location
    var base = document.querySelector('meta[name="base-url"]');
    var baseUrl = base ? base.getAttribute('content') : window.location.origin + '/ai-job-portal/public';
    var baseTag = document.querySelector('base');
    if (!baseUrl || baseUrl === '') {
        baseUrl = baseTag ? baseTag.getAttribute('href') : window.location.origin + '/ai-job-portal/public';
    }
    baseUrl = baseUrl.replace(/\/+$/, '');

    var askUrl  = baseUrl + '/recruiter/chatbot/ask';
    var suggUrl = baseUrl + '/recruiter/chatbot/suggestions';

    var isLoading  = false;
    var hasLoadedSuggestions = false;
    var recognition = null;
    var isListening = false;
    var isSpeaking = false;
    var shouldSpeakNextReply = false;
    var voiceTurnSubmitted = false;

    // ── FIX: Prevent background scroll when hovering over widget ──
    // Trap wheel events inside the messages container so the page never scrolls
    msgBox.addEventListener('wheel', function (e) {
        var delta = e.deltaY;
        var canScrollDown = msgBox.scrollTop + msgBox.clientHeight < msgBox.scrollHeight;
        var canScrollUp   = msgBox.scrollTop > 0;

        // If there's content to scroll in that direction, let it scroll and stop propagation
        if ((delta > 0 && canScrollDown) || (delta < 0 && canScrollUp)) {
            // Let the browser's native scroll happen — just stop the event from propagating
            e.stopPropagation();
        } else {
            // At a boundary — prevent default to stop page scroll, don't stopPropagation
            e.preventDefault();
        }
    }, { passive: false });

    // Also trap touchmove to prevent background page scroll on mobile
    msgBox.addEventListener('touchmove', function (e) {
        e.stopPropagation();
    }, { passive: true });

    // ── Toggle widget ──
    function openWidget() {
        widget.classList.add('open');
        fab.style.display = 'none';
        input.focus();
        if (!hasLoadedSuggestions) {
            loadSuggestions();
        }
    }

    function closeWidget() {
        widget.classList.remove('open');
        fab.style.display = 'flex';
        if (recognition && isListening) {
            recognition.stop();
        }
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }
        setVoiceSpeaking(false);
    }

    fab.addEventListener('click', openWidget);
    close.addEventListener('click', closeWidget);

    // ── Load suggestion chips ──
    function loadSuggestions() {
        fetch(suggUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
            cache: 'no-cache'
        })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (data) {
                if (!data || !data.suggestions) return;
                hasLoadedSuggestions = true;
                data.suggestions.forEach(function (item) {
                    var text = typeof item === 'string' ? item : (item && item.text ? item.text : '');
                    var mode = typeof item === 'object' && item ? (item.mode || 'send') : 'send';
                    if (!text) return;
                    var btn = document.createElement('button');
                    btn.textContent = text;
                    btn.type = 'button';
                    btn.addEventListener('click', function () {
                        input.value = text;
                        input.focus();
                        if (mode === 'edit') {
                            var marker = input.value.indexOf('#ID');
                            if (marker >= 0 && input.setSelectionRange) {
                                input.setSelectionRange(marker + 1, marker + 3);
                            }
                            return;
                        }
                        sendMessage();
                    });
                    suggBox.appendChild(btn);
                });
            })
            .catch(function (err) {
                console.warn('HireMate: Could not load suggestions (' + err.message + ')');
            });
    }

    // ── Send message ──
    function sendMessage() {
        var text = input.value.trim();
        if (text === '' || isLoading) return;

        input.value = '';
        isLoading = true;
        sendBtn.disabled = true;
        if (voiceBtn && recognition) voiceBtn.disabled = true;

        // Append user message
        appendMessage('user', text);

        // Show typing indicator
        var typingDiv = document.createElement('div');
        typingDiv.className = 'hm-msg bot';
        typingDiv.id = 'hmTypingIndicator';
        typingDiv.innerHTML = '<div class="hm-msg-bubble"><div class="hm-typing"><span></span><span></span><span></span></div></div>';
        msgBox.appendChild(typingDiv);
        scrollBottom();

        var formData = new FormData();
        formData.append('question', text);

        fetch(askUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: formData
        })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (data) {
                var typing = document.getElementById('hmTypingIndicator');
                if (typing) typing.remove();

                if (data.success && data.answer) {
                    appendMessage('bot', data.answer);
                    if (voiceBtn && recognition) voiceBtn.disabled = false;
                    speakRecruiterReply(data.answer);
                } else {
                    var reply = data.answer || 'Sorry, I couldn\'t process that. Please try asking differently.';
                    appendMessage('bot', reply);
                    if (voiceBtn && recognition) voiceBtn.disabled = false;
                    speakRecruiterReply(reply);
                }
            })
            .catch(function (err) {
                var typing = document.getElementById('hmTypingIndicator');
                if (typing) typing.remove();
                var reply = 'Connection error: ' + err.message + '. Make sure you are logged in as a recruiter.';
                appendMessage('bot', reply);
                if (voiceBtn && recognition) voiceBtn.disabled = false;
                speakRecruiterReply(reply);
            })
            .finally(function () {
                isLoading = false;
                sendBtn.disabled = false;
                if (voiceBtn && recognition) voiceBtn.disabled = false;
                scrollBottom();
            });
    }

    function speakRecruiterReply(text) {
        if (!shouldSpeakNextReply) return;
        shouldSpeakNextReply = false;
        if (!('speechSynthesis' in window) || !text) return;

        window.speechSynthesis.cancel();
        var utterance = new SpeechSynthesisUtterance(String(text).replace(/\s+/g, ' ').trim());
        utterance.lang = 'en-US';
        utterance.rate = 1;
        utterance.pitch = 1;
        utterance.onstart = function () {
            setVoiceSpeaking(true);
        };
        utterance.onend = function () {
            setVoiceSpeaking(false);
        };
        utterance.onerror = function () {
            setVoiceSpeaking(false);
        };
        window.speechSynthesis.speak(utterance);
    }

    function appendMessage(role, text) {
        var div = document.createElement('div');
        div.className = 'hm-msg ' + role;

        var bubble = document.createElement('div');
        bubble.className = 'hm-msg-bubble';
        bubble.textContent = text;
        div.appendChild(bubble);

        var time = document.createElement('div');
        time.className = 'hm-msg-time';
        time.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        div.appendChild(time);

        msgBox.appendChild(div);
        requestAnimationFrame(scrollBottom);
    }

    function scrollBottom() {
        msgBox.scrollTop = msgBox.scrollHeight;
    }

    // ── Events ──
    function setVoiceListening(listening) {
        isListening = listening;
        if (!voiceBtn) return;
        voiceBtn.classList.toggle('is-listening', listening);
        updateVoiceButtonState();
    }

    function setVoiceSpeaking(speaking) {
        isSpeaking = speaking;
        if (!voiceBtn) return;
        voiceBtn.classList.toggle('is-speaking', speaking);
        updateVoiceButtonState();
    }

    function stopVoiceAudio() {
        shouldSpeakNextReply = false;
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }
        setVoiceSpeaking(false);
    }

    function updateVoiceButtonState() {
        if (!voiceBtn) return;
        var isStopping = isListening || isSpeaking;
        voiceBtn.setAttribute('aria-label', isSpeaking ? 'Stop audio' : (isListening ? 'Stop voice chat' : 'Start voice chat'));
        voiceBtn.setAttribute('title', isSpeaking ? 'Stop audio' : (isListening ? 'Stop voice chat' : 'Start voice chat'));
        var mic = voiceBtn.querySelector('.hm-chat-voice-mic');
        var stop = voiceBtn.querySelector('.hm-chat-voice-stop');
        if (mic) mic.style.display = isStopping ? 'none' : 'block';
        if (stop) stop.style.display = isStopping ? 'block' : 'none';
    }

    function initVoiceChat() {
        if (!voiceBtn) return;

        var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            voiceBtn.disabled = true;
            voiceBtn.setAttribute('title', 'Voice chat is not supported in this browser');
            voiceBtn.setAttribute('aria-label', 'Voice chat is not supported in this browser');
            return;
        }

        recognition = new SpeechRecognition();
        recognition.lang = 'en-US';
        recognition.interimResults = true;
        recognition.continuous = false;

        recognition.addEventListener('start', function () {
            setVoiceListening(true);
            input.placeholder = 'Listening...';
        });

        recognition.addEventListener('result', function (event) {
            var transcript = '';
            for (var i = event.resultIndex; i < event.results.length; i++) {
                transcript += event.results[i][0].transcript;
            }
            input.value = transcript.trim();

            var latest = event.results[event.results.length - 1];
            if (latest && latest.isFinal && input.value.trim()) {
                voiceTurnSubmitted = true;
                shouldSpeakNextReply = true;
                recognition.stop();
                sendMessage();
            }
        });

        recognition.addEventListener('end', function () {
            setVoiceListening(false);
            input.placeholder = 'Ask about your hiring data...';
            if (!voiceTurnSubmitted) {
                shouldSpeakNextReply = false;
            }
        });

        recognition.addEventListener('error', function () {
            setVoiceListening(false);
            input.placeholder = 'Ask about your hiring data...';
            shouldSpeakNextReply = false;
        });

        voiceBtn.addEventListener('click', function () {
            openWidget();

            if (isSpeaking) {
                stopVoiceAudio();
                return;
            }

            if (isListening) {
                recognition.stop();
                return;
            }

            stopVoiceAudio();

            input.value = '';
            voiceTurnSubmitted = false;
            shouldSpeakNextReply = true;
            try {
                recognition.start();
            } catch (e) {
                setVoiceListening(false);
                shouldSpeakNextReply = false;
            }
        });
    }

    initVoiceChat();

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && widget.classList.contains('open')) {
            closeWidget();
        }
    });
})();
</script>
