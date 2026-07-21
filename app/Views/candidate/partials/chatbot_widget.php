<!-- ─── Candidate AI Assistant Widget ─── -->
<meta name="csrf-name" content="<?= csrf_token() ?>">
<meta name="csrf-hash" content="<?= csrf_hash() ?>">
<style>
.hirebot-widget {
  position: fixed;
  bottom: 48px;
  right: 28px;
  display: flex;
  align-items: center;
  z-index: 9999;
  font-family: inherit;
}

/* ===== Badge — force circle, override any inherited button styles ===== */
.hirebot-fab {
  all: unset;
  box-sizing: border-box;
  width: 92px;
  height: 92px;
  min-width: 92px;
  min-height: 92px;
  border-radius: 50% !important;
  padding: 3px;
  background: var(--gradient-primary);
  box-shadow: 0 8px 22px rgba(13, 138, 144, 0.3);
  cursor: pointer;
  flex-shrink: 0;
  display: flex;
}

.hirebot-fab-inner {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: var(--gradient-soft);
  display: flex;
  align-items: center;
  justify-content: center;
}

.hirebot-fab-text {
  background: linear-gradient(135deg, var(--primary-dark), var(--secondary-dark));
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  font-size: 13px;
  font-weight: 700;
  line-height: 1.15;
  text-align: center;
}

/* ===== Tooltip ===== */
.hirebot-tooltip {
  position: relative;
  background: var(--gradient-primary);
  color: #fff;
  padding: 13px 24px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  font-style: italic;
  white-space: nowrap;
  box-shadow: 0 8px 20px rgba(13, 138, 144, 0.3);
  margin-right: 14px;
  transform-origin: right center;
  animation: hirebot-pop 5s ease-in-out infinite;
}

.hirebot-tooltip::after {
  content: '';
  position: absolute;
  top: 50%;
  right: -6px;
  width: 12px;
  height: 12px;
  background: var(--secondary);
  transform: translateY(-50%) rotate(45deg);
}

@keyframes hirebot-pop {
  0%, 10%   { opacity: 0; transform: scale(0.7) translateX(8px); }
  20%       { opacity: 1; transform: scale(1.06) translateX(0); }
  26%       { transform: scale(1) translateX(0); }
  80%       { opacity: 1; transform: scale(1) translateX(0); }
  92%, 100% { opacity: 0; transform: scale(0.85) translateX(8px); }
}

/* ===== Dark theme overrides ===== */
body.dark .hirebot-fab {
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.45);
}

body.dark .hirebot-tooltip {
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
}

.hm-candidate-chat-widget {
    position: fixed;
    bottom: 92px;
    right: 24px;
    width: 510px;
    height: 450px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.04);
    z-index: 10000;
    display: none;
    flex-direction: column;
    overflow: hidden;
}

/* ===== Quick actions row (row 1) and suggestions row (row 2) ===== */
.hm-candidate-chat-quick-actions,
.hm-candidate-chat-suggestions {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  overflow-x: auto;
  overflow-y: hidden;
  scrollbar-width: none;
  -ms-overflow-style: none;
  scroll-behavior: smooth;
}

.hm-candidate-chat-quick-actions::-webkit-scrollbar,
.hm-candidate-chat-suggestions::-webkit-scrollbar {
  display: none;
}

.hm-candidate-chat-quick-actions button,
.hm-candidate-chat-suggestions button {
  flex-shrink: 0;
  white-space: nowrap;
  margin: 0 !important;
}
</style>

<div class="hirebot-widget" id="hirebotWidget">
  <div class="hirebot-tooltip" id="hirebotTooltip">
    Explore top career content
  </div>
  <button type="button" class="hirebot-fab" id="hmCandidateChatFab" aria-label="Open HireBot assistant">
    <span class="hirebot-fab-inner">
      <span class="hirebot-fab-text">Hire<br>Bot</span>
    </span>
  </button>
</div>

<div class="hm-candidate-chat-widget" id="hmCandidateChatWidget">
    <div class="hm-candidate-chat-header">
        <div>
            <strong>HireMate Assistant</strong>
            <small>Ask about jobs, applications, and interviews</small>
        </div>
        <button type="button" class="hm-candidate-chat-header-close" id="hmCandidateChatClose" aria-label="Close">×</button>
    </div>

    <div class="hm-candidate-chat-messages" id="hmCandidateChatMessages">
        <div class="hm-candidate-chat-row bot"><div class="hm-candidate-chat-bubble bot">Welcome! 🚀 I'm your personal AI career coach and ChatBot</div></div>
        <div class="hm-candidate-chat-row bot"><div class="hm-candidate-chat-bubble bot">Hi! I can help you review jobs, applications, career planning, interviews, and your profile. Ask me anything.</div></div> 
    </div>

    <?php
        // Resolve the candidate's target role once. If it isn't set yet,
        // the JS below will ask the user for it before firing any quick action.
        $hmTargetRole = (isset($active_sessions) && !empty($active_sessions[0]['target_role']))
            ? $active_sessions[0]['target_role']
            : '';
    ?>

    <!-- Row 1: Quick Actions (career-mentor shortcuts) -->
    <div class="hm-candidate-chat-quick-actions" id="hmCandidateQuickActions" data-target-role="<?= esc($hmTargetRole, 'attr') ?>">
        <button type="button" class="btn btn-sm btn-outline-primary" data-action="career-plan">
            <i class="fas fa-briefcase me-1"></i> Career Plan
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary" data-action="skill-gap">
            <i class="fas fa-chart-bar me-1"></i> Skill Gap
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary" data-action="interview-prep">
            <i class="fas fa-microphone me-1"></i> Interview Prep
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary" data-action="resume-review">
            <i class="fas fa-file-alt me-1"></i> Resume Review
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary" data-action="salary-tips">
            <i class="fas fa-dollar-sign me-1"></i> Salary Tips
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary" data-action="enroll-course">
            <i class="fas fa-graduation-cap me-1"></i> Enroll in Course
        </button>
    </div>

    <!-- Row 2: Suggestions (loaded dynamically) -->
    <div class="hm-candidate-chat-suggestions" id="hmCandidateChatSuggestions"></div>

    <div class="hm-candidate-chat-input-wrap">
        <input type="text" class="hm-candidate-chat-input" id="hmCandidateChatInput" placeholder="Ask about your job portal data..." autocomplete="off">
        <button type="button" class="hm-candidate-chat-voice" id="hmCandidateChatVoice" aria-label="Start voice chat" title="Start voice chat">
            <i class="fas fa-microphone"></i>
        </button>
        <button type="button" class="hm-candidate-chat-send" id="hmCandidateChatSend">➤</button>
    </div>
</div>

<script>
(function () {
    const fab          = document.getElementById('hmCandidateChatFab');
    const widget       = document.getElementById('hmCandidateChatWidget');
    const closeBtn      = document.getElementById('hmCandidateChatClose');
    const messages      = document.getElementById('hmCandidateChatMessages');
    const input          = document.getElementById('hmCandidateChatInput');
    const voice          = document.getElementById('hmCandidateChatVoice');
    const send            = document.getElementById('hmCandidateChatSend');
    const suggestions   = document.getElementById('hmCandidateChatSuggestions');
    const quickActionsWrap = document.getElementById('hmCandidateQuickActions');

    if (!fab || !widget || !messages || !input || !send) return;

    const baseUrl = (document.querySelector('meta[name="base-url"]')?.getAttribute('content')
        || window.location.origin + '/ai-job-portal/public').replace(/\/+$/, '');

    const askUrl     = baseUrl + '/candidate/chatbot/ask';         // general chatbot Q&A
    const suggUrl    = baseUrl + '/candidate/chatbot/suggestions'; // suggestion chips
    const mentorUrl  = baseUrl + '/premium-mentor/chat';           // career-mentor topics

    let chatSessionId = '';
    let recognition = null;
    let isListening = false;
    let isSpeaking = false;
    let shouldSpeakNextReply = false;
    let voiceTurnSubmitted = false;

    // ── Target role state ────────────────────────────────────────────
    // Read whatever role the backend already knows about (may be empty).
    let targetRole = (quickActionsWrap && quickActionsWrap.dataset.targetRole) || '';
    // When a quick action is clicked without a known role, we stash the
    // action key here and treat the user's NEXT typed message as their role.
    let pendingRoleAction = null;

    const QUICK_ACTION_TEMPLATES = {
        'career-plan':   role => `Help me create a career plan to become a ${role}`,
        'skill-gap':     role => `Do a skill gap analysis for my ${role} target role`,
        'interview-prep':role => `Help me prepare for ${role} interviews`,
        'resume-review': role => `Review and optimize my resume for a ${role} role`,
        'salary-tips':   role => `Give me salary negotiation tips for a ${role} role`,
        'enroll-course': role => `I'm ready to work on 'Enroll in an introductory ${role} course'. Any tips on getting started?`
    };

    function runQuickAction(actionKey) {
        const template = QUICK_ACTION_TEMPLATES[actionKey];
        if (!template) return;

        if (!targetRole) {
            // Ask for the role first instead of guessing or defaulting.
            pendingRoleAction = actionKey;
            addMessage("Before I dive in — what role are you targeting? (e.g. Software Engineer, Data Analyst, Product Manager)", 'bot');
            input.placeholder = 'Type your target role...';
            widget.classList.add('open');
            fab.style.display = 'none';
            input.focus();
            return;
        }

        input.value = template(targetRole);
        sendMessage(true); // quick actions always go to the mentor endpoint
    }

    // ── Topic classifier ────────────────────────────────────────────
    // Decides whether a typed message belongs to the premium mentor
    // (career planning / skill dev / interview prep / resume / salary)
    // or the general candidate chatbot.
    const MENTOR_KEYWORDS = [
        // career planning
        'career plan', 'career path', 'career goal', 'become a', 'career growth', 'career advice', 'career roadmap',
        // skill development
        'skill gap', 'skill development', 'upskill', 'learn skill', 'improve my skill', 'course recommendation', 'certification',
        // interview prep
        'interview', 'mock interview', 'interview question', 'interview tips',
        // resume optimization
        'resume', 'cv review', 'optimize my resume', 'resume feedback', 'resume tips',
        // salary negotiation
        'salary', 'negotiat', 'compensation', 'pay raise', 'offer negotiation'
    ];

    function isMentorTopic(text) {
        const lower = text.toLowerCase();
        return MENTOR_KEYWORDS.some(kw => lower.includes(kw));
    }

    // ── CSRF helper ─────────────────────────────────────────────────
    function getCsrfData() {
        return {
            name: document.querySelector('meta[name="csrf-name"]')?.getAttribute('content') || '',
            hash: document.querySelector('meta[name="csrf-hash"]')?.getAttribute('content') || ''
        };
    }

    function formatFeatureLabel(feature) {
        return String(feature).replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    function speakCandidateReply(text) {
        if (!shouldSpeakNextReply) return;
        shouldSpeakNextReply = false;
        if (!('speechSynthesis' in window) || !text) return;

        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(String(text).replace(/\s+/g, ' ').trim());
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

    // ── Message rendering ───────────────────────────────────────────
    function addMessage(text, type, premiumFeatures) {
        const row = document.createElement('div');
        row.className = 'hm-candidate-chat-row ' + type;

        const bubble = document.createElement('div');
        bubble.className = 'hm-candidate-chat-bubble ' + type;
        bubble.textContent = text;

        if (type === 'bot' && Array.isArray(premiumFeatures) && premiumFeatures.length) {
            const wrap = document.createElement('div');
            wrap.style.cssText = 'margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;';
            premiumFeatures.forEach(f => {
                const chip = document.createElement('span');
                chip.textContent = formatFeatureLabel(f);
                chip.className = 'hm-theme-chip';
                wrap.appendChild(chip);
            });
            bubble.appendChild(document.createElement('br'));
            bubble.appendChild(wrap);
        }

        row.appendChild(bubble);
        messages.appendChild(row);
        messages.scrollTop = messages.scrollHeight;
        return row;
    }

    // Adds an animated 3-dot "typing" bubble and returns the row so it can be removed/replaced later.
    function addTypingIndicator() {
        const row = document.createElement('div');
        row.className = 'hm-candidate-chat-row bot';

        const bubble = document.createElement('div');
        bubble.className = 'hm-candidate-chat-bubble bot';

        const dots = document.createElement('span');
        dots.className = 'hm-typing-dots';
        dots.innerHTML = '<span></span><span></span><span></span>';

        bubble.appendChild(dots);
        row.appendChild(bubble);
        messages.appendChild(row);
        messages.scrollTop = messages.scrollHeight;
        return { row, bubble };
    }

    function setLoading(isLoading) {
        send.disabled = isLoading;
        input.disabled = isLoading;
        if (voice && recognition) voice.disabled = isLoading;
        send.textContent = isLoading ? '…' : '➤';
    }

    // ── Suggestions ─────────────────────────────────────────────────
    function loadSuggestions() {
        fetch(suggUrl, { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data || !Array.isArray(data.suggestions)) return;
                suggestions.innerHTML = '';
                data.suggestions.forEach(item => {
                    const label   = typeof item === 'string' ? item : (item && item.text ? item.text : '');
                    const mode    = typeof item === 'object' && item ? (item.mode || 'send') : 'send';
                    const icon    = typeof item === 'object' && item ? item.icon : null;
                    const message = (typeof item === 'object' && item && item.message) ? item.message : label;
                    if (!label) return;

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    if (icon) {
                        btn.innerHTML = '<i class="' + icon + ' hm-candidate-chat-suggestion-icon"></i>' + label;
                    } else {
                        btn.textContent = label;
                    }

                    btn.addEventListener('click', () => {
                        input.value = message;
                        input.focus();
                        if (mode === 'edit') {
                            const marker = input.value.indexOf('#ID');
                            if (marker >= 0 && input.setSelectionRange) {
                                input.setSelectionRange(marker + 1, marker + 3);
                            }
                            return;
                        }
                        sendMessage();
                    });
                    suggestions.appendChild(btn);
                });
            }).catch(() => {});
    }

    // ── Sending to the general chatbot endpoint ─────────────────────
    function sendToChatbot(question, typing) {
        fetch(askUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'Accept': 'application/json' },
            body: new URLSearchParams({ question: question }),
            credentials: 'same-origin'
        })
            .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status)))
            .then(data => {
                typing.row.remove();
                const reply = data && data.answer ? data.answer : 'I could not answer that right now.';
                addMessage(reply, 'bot');
                speakCandidateReply(reply);
            })
            .catch(() => {
                typing.row.remove();
                const reply = 'The assistant is temporarily unavailable.';
                addMessage(reply, 'bot');
                speakCandidateReply(reply);
            })
            .finally(() => {
                setLoading(false);
                input.focus();
            });
    }

    // ── Sending to the premium mentor endpoint ──────────────────────
    function sendToMentor(message, typing) {
        const csrf = getCsrfData();
        const postData = new URLSearchParams({ message: message, session_id: chatSessionId });
        if (csrf.name) postData.append(csrf.name, csrf.hash);

        fetch(mentorUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'Accept': 'application/json' },
            body: postData,
            credentials: 'same-origin'
        })
            .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status)))
            .then(res => {
                typing.row.remove();
                if (res && res.message) {
                    chatSessionId = res.session_id || chatSessionId;
                    addMessage(res.message, 'bot', res.premium_features || []);
                    speakCandidateReply(res.message);
                    if (res.progress_tracking && res.progress_tracking.last_nudge) {
                        addMessage('💡 ' + res.progress_tracking.last_nudge, 'bot');
                    }
                } else if (res && res.error) {
                    addMessage(res.error, 'bot');
                    speakCandidateReply(res.error);
                } else {
                    const reply = 'No response received. Please try again.';
                    addMessage(reply, 'bot');
                    speakCandidateReply(reply);
                }
            })
            .catch(() => {
                typing.row.remove();
                const reply = 'The assistant is temporarily unavailable.';
                addMessage(reply, 'bot');
                speakCandidateReply(reply);
            })
            .finally(() => {
                setLoading(false);
                input.focus();
            });
    }

    // ── Main send handler: one place, routes based on topic ─────────
    function sendMessage(forceMentor) {
        const message = input.value.trim();
        if (!message) return;

        // If we're waiting on a target role for a quick action, treat this
        // message as the role answer rather than sending it to the backend.
        if (pendingRoleAction) {
            const actionKey = pendingRoleAction;
            pendingRoleAction = null;
            targetRole = message;
            if (quickActionsWrap) quickActionsWrap.dataset.targetRole = targetRole;
            input.value = '';
            input.placeholder = 'Ask about your job portal data...';
            addMessage(message, 'user');

            const template = QUICK_ACTION_TEMPLATES[actionKey];
            if (template) {
                input.value = template(targetRole);
                sendMessage(true);
            }
            return;
        }

        addMessage(message, 'user');
        input.value = '';
        setLoading(true);
        const typing = addTypingIndicator();

        const useMentor = forceMentor === true || isMentorTopic(message);

        if (useMentor) {
            sendToMentor(message, typing);
        } else {
            sendToChatbot(message, typing);
        }
    }

    if (quickActionsWrap) {
        quickActionsWrap.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('click', () => runQuickAction(btn.getAttribute('data-action')));
        });
    }

    // ── Widget open/close ────────────────────────────────────────────
    function setVoiceListening(listening) {
        isListening = listening;
        if (!voice) return;
        voice.classList.toggle('is-listening', listening);
        updateVoiceButtonState();
    }

    function setVoiceSpeaking(speaking) {
        isSpeaking = speaking;
        if (!voice) return;
        voice.classList.toggle('is-speaking', speaking);
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
        if (!voice) return;
        const isStopping = isListening || isSpeaking;
        voice.setAttribute('aria-label', isSpeaking ? 'Stop audio' : (isListening ? 'Stop voice chat' : 'Start voice chat'));
        voice.setAttribute('title', isSpeaking ? 'Stop audio' : (isListening ? 'Stop voice chat' : 'Start voice chat'));
        const icon = voice.querySelector('i');
        if (icon) {
            icon.className = isStopping ? 'fas fa-stop' : 'fas fa-microphone';
        }
    }

    function initVoiceChat() {
        if (!voice) return;

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            voice.disabled = true;
            voice.setAttribute('title', 'Voice chat is not supported in this browser');
            voice.setAttribute('aria-label', 'Voice chat is not supported in this browser');
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
            let transcript = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                transcript += event.results[i][0].transcript;
            }
            input.value = transcript.trim();

            const latest = event.results[event.results.length - 1];
            if (latest && latest.isFinal && input.value.trim()) {
                voiceTurnSubmitted = true;
                shouldSpeakNextReply = true;
                recognition.stop();
                sendMessage(false);
            }
        });

        recognition.addEventListener('end', function () {
            setVoiceListening(false);
            input.placeholder = 'Ask about your job portal data...';
            if (!voiceTurnSubmitted) {
                shouldSpeakNextReply = false;
            }
        });

        recognition.addEventListener('error', function () {
            setVoiceListening(false);
            input.placeholder = 'Ask about your job portal data...';
            shouldSpeakNextReply = false;
        });

        voice.addEventListener('click', function () {
            widget.classList.add('open');
            fab.style.display = 'none';

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

    fab.addEventListener('click', function () {
        widget.classList.add('open');
        fab.style.display = 'none';
        input.focus();
        if (!suggestions.dataset.loaded) {
            suggestions.dataset.loaded = '1';
            loadSuggestions();
        }
    });
    closeBtn.addEventListener('click', function () {
        widget.classList.remove('open');
        fab.style.display = 'flex';
        if (recognition && isListening) {
            recognition.stop();
        }
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }
        setVoiceSpeaking(false);
    });
    send.addEventListener('click', () => sendMessage(false));
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage(false);
        }
    });

    // ── FAB attention pulse ──────────────────────────────────────────
    function pulseFabAttention() {
        if (widget.classList.contains('open')) return;
        fab.classList.add('hm-fab-attention');
        setTimeout(function () {
            fab.classList.remove('hm-fab-attention');
        }, 2400);
    }
    setTimeout(pulseFabAttention, 800);
    setInterval(pulseFabAttention, 5 * 60 * 1000);
})();
</script>