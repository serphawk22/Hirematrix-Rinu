<!-- ─── Candidate AI Assistant Widget ─── -->
<style>
.hm-candidate-chat-fab {
    position: fixed;
    bottom: 34px;
    right: 34px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--candidate-accent, var(--primary, #1FB7B5));
    color: #fff;
    border: none;
    box-shadow: none;
    cursor: pointer;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}
.hm-candidate-chat-fab:hover { transform: scale(1.05); }
.hm-candidate-chat-send:focus,
.hm-candidate-chat-send:focus-visible,
.hm-candidate-chat-header-close:focus,
.hm-candidate-chat-header-close:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(31, 183, 181, 0.18);
}
.hm-candidate-chat-fab:focus,
.hm-candidate-chat-fab:focus-visible {
    outline: none;
    box-shadow: none;
}
.hm-candidate-chat-widget {
    position: fixed;
    bottom: 92px;
    right: 24px;
    width: 910px;
    height: 580px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.04);
    z-index: 10000;
    display: none;
    flex-direction: column;
    overflow: hidden;
}
.hm-candidate-chat-widget.open { display: flex; }
.hm-candidate-chat-header {
    padding: 14px 16px;
    background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.hm-candidate-chat-header strong { display: block; font-size: 15px; line-height: 1.2; }
.hm-candidate-chat-header small { display: block; margin-top: 2px; opacity: 0.9; font-size: 12px; }
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
.hm-candidate-chat-header-close:hover { background: rgba(255, 255, 255, 0.28); }
.hm-candidate-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px 16px 0 16px;
    background: #f4f8ff;
}
.hm-candidate-chat-row { display: block; width: 100%; }
.hm-candidate-chat-row.user { text-align: right; }
.hm-candidate-chat-row.bot { text-align: left; }
.hm-candidate-chat-bubble {
    display: inline-block;
    max-width: 85%;
    padding: 10px 14px;
    border-radius: 14px;
    margin-bottom: 10px;
    font-size: 13.5px;
    line-height: 1.5;
    white-space: pre-wrap;
    text-align: left;
}
.hm-candidate-chat-bubble.bot { background: #eef4ff; color: #14213d; }
.hm-candidate-chat-bubble.user { background: var(--candidate-accent, var(--primary, #1FB7B5)); color: #fff; }
 
/* Typing indicator: 3 animated dots */
.hm-typing-dots { display: inline-flex; align-items: center; gap: 4px; padding: 2px 4px; }
.hm-typing-dots span {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--candidate-accent, var(--primary, #1FB7B5));
    opacity: 0.4;
    animation: hmTypingBounce 1.2s infinite ease-in-out;
}
.hm-typing-dots span:nth-child(1) { animation-delay: 0s; }
.hm-typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.hm-typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes hmTypingBounce {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
    30% { transform: translateY(-4px); opacity: 1; }
}
 
.hm-candidate-chat-suggestions { display: flex; flex-wrap: wrap; gap: 6px; padding: 0 16px 10px; background: #f4f8ff; }
.hm-candidate-chat-suggestions button { border: 1px solid rgba(31, 183, 181, 0.28); background: rgba(31, 183, 181, 0.08); color: var(--candidate-accent-dark, var(--primary-dark, #0D8A90)); padding: 6px 14px; border-radius: 999px; font-size: 12px; cursor: pointer; transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease; }
.hm-candidate-chat-suggestions button:hover { background: rgba(31, 183, 181, 0.16); border-color: rgba(31, 183, 181, 0.42); color: var(--candidate-accent-dark, var(--primary-dark, #0D8A90)); }
.hm-candidate-chat-suggestions button:focus,
.hm-candidate-chat-suggestions button:focus-visible,
.hm-candidate-chat-suggestions button:active {
    outline: none;
    box-shadow: none;
    border-color: rgba(31, 183, 181, 0.42);
}
 
.hm-candidate-chat-quick-actions { display: flex; flex-wrap: wrap; gap: 6px; padding: 8px 16px; background: #f4f8ff; }
 
.hm-candidate-chat-input-wrap { display: flex; gap: 8px; padding: 10px 14px; border-top: 1px solid #dbeafe; background: #fff; }
.hm-candidate-chat-input { flex: 1; border: 1px solid #dbeafe; border-radius: 999px; padding: 10px 14px; font-size: 14px; color: #0f172a; }
.hm-candidate-chat-input:focus { outline: none; border-color: var(--candidate-accent, var(--primary, #1FB7B5)); box-shadow: 0 0 0 3px rgba(31, 183, 181, 0.12); }
.hm-candidate-chat-send { border: none; background: var(--candidate-accent, var(--primary, #1FB7B5)); color: #fff; width: 38px; height: 38px; border-radius: 50%; cursor: pointer; }
body.dark .hm-candidate-chat-widget {
    background: #050505;
    border: 1px solid #23343A;
    box-shadow: none !important;
}

body.dark .hm-candidate-chat-header small {
    color: #fff !important;
    opacity: 1;
}
body.dark .hm-candidate-chat-messages {
    background: var(--card) !important;
}
body.dark .hm-candidate-chat-bubble.bot {
    background: #162327 !important;
    color: #F4F8FF;
}
body.dark .hm-candidate-chat-quick-actions,body.dark .hm-candidate-chat-input-wrap{
     background: var(--card) !important;
}
body.dark .hm-candidate-chat-suggestions {
    background: var(--card) !important;
}
body.dark .hm-candidate-chat-suggestions button {
    background: var(--card) !important;
    border-color: rgba(31, 183, 181, 0.42);
    color: #D8FFFF;
}
body.dark .hm-candidate-chat-suggestions button:hover,
body.dark .hm-candidate-chat-suggestions button:focus {
    background: rgba(31, 183, 181, 0.22);
    border-color: rgba(31, 183, 181, 0.75);
    color: #FFFFFF;
}
body.dark .hm-candidate-chat-input-wrap {
    background: #030707;
    border-top-color: #23343A;
}
body.dark .hm-candidate-chat-input {
    background: #111315;
    border-color: #3A444A;
    color: #F8FAFC;
}
body.dark .hm-candidate-chat-input::placeholder {
    color: #AAB4BF;
}
body.dark .hm-candidate-chat-input:focus {
    border-color: var(--candidate-accent, var(--primary, #1FB7B5));
    box-shadow: 0 0 0 3px rgba(31, 183, 181, 0.16);
}
body.dark .hm-candidate-chat-bubble.user{
    background-color:#0D8A90 !important;
}
@media (max-width: 480px) {
    .hm-candidate-chat-widget { right: 12px; left: 12px; width: auto; height: 70vh; bottom: 80px; }
    .hm-candidate-chat-fab { right: 16px; bottom: 16px; }
}
.hm-candidate-chat-fab.hm-fab-attention {
    background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%);
    animation: hmFabAttention 1.2s ease-in-out 2;
}
@keyframes hmFabAttention {
    0%   { transform: scale(1.1); box-shadow:none !important; }
    50%  { transform: scale(1.4); box-shadow: none !important; }
    100% { transform: scale(1.1); box-shadow: none !important; }
}
.hm-theme-chip {
    font-size: 11px;
    border-radius: 999px;
    padding: 3px 8px;
    display: inline-block;
    color: var(--primary) !important;
    background: #ffffff !important;
    border: 1px solid var(--primary) !important;
}

/* Dark theme override */
body.dark.candidate-app .hm-theme-chip,
html.hm-dark-preload .hm-theme-chip {
    background: #162327 !important;
    color: #fff !important;
    border: 1px solid var(--primary) !important;
}
</style>

<meta name="csrf-name" content="<?= csrf_token() ?>">
<meta name="csrf-hash" content="<?= csrf_hash() ?>">

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
        <div class="hm-candidate-chat-row bot"><div class="hm-candidate-chat-bubble bot">Welcome! 🚀 I'm your personal AI career coach and ChatBot</div></div>
        <div class="hm-candidate-chat-row bot"><div class="hm-candidate-chat-bubble bot">Hi! I can help you review jobs, applications, saved roles, interviews, and your profile. Ask me anything.</div></div>
        <div class="hm-candidate-chat-row bot"><div class="hm-candidate-chat-bubble bot">I can help you with career planning, skill development, interview preparation, resume optimization, and salary negotiation.</div></div>
    </div>

    <?php
        // Resolve the candidate's target role once. If it isn't set yet,
        // the JS below will ask the user for it before firing any quick action.
        $hmTargetRole = (isset($active_sessions) && !empty($active_sessions[0]['target_role']))
            ? $active_sessions[0]['target_role']
            : '';
    ?>

    <!-- Quick Actions (career-mentor shortcuts) -->
    <div class="hm-candidate-chat-quick-actions" id="hmCandidateQuickActions" data-target-role="<?= esc($hmTargetRole, 'attr') ?>">
        <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2" data-action="career-plan">
         <i class="fas fa-briefcase me-1"></i> Career Plan  
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2" data-action="skill-gap">
            <i class="fas fa-chart-bar me-1"></i> Skill Gap
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2" data-action="interview-prep">
            <i class="fas fa-microphone me-1"></i> Interview Prep
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2" data-action="resume-review">
            <i class="fas fa-file-alt me-1"></i> Resume Review
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2" data-action="salary-tips">
            <i class="fas fa-dollar-sign me-1"></i> Salary Tips
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary me-2 mb-2" data-action="enroll-course">
            <i class="fas fa-graduation-cap me-1"></i> Enroll in Course
        </button>
    </div>

    <div class="hm-candidate-chat-suggestions" id="hmCandidateChatSuggestions"></div>

    <div class="hm-candidate-chat-input-wrap">
        <input type="text" class="hm-candidate-chat-input" id="hmCandidateChatInput" placeholder="Ask about your job portal data..." autocomplete="off">
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
                        btn.innerHTML = '<i class="' + icon + '" style="margin-right:4px;"></i>' + label;
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
                addMessage(data && data.answer ? data.answer : 'I could not answer that right now.', 'bot');
            })
            .catch(() => {
                typing.row.remove();
                addMessage('The assistant is temporarily unavailable.', 'bot');
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
                    if (res.progress_tracking && res.progress_tracking.last_nudge) {
                        addMessage('💡 ' + res.progress_tracking.last_nudge, 'bot');
                    }
                } else if (res && res.error) {
                    addMessage(res.error, 'bot');
                } else {
                    addMessage('No response received. Please try again.', 'bot');
                }
            })
            .catch(() => {
                typing.row.remove();
                addMessage('The assistant is temporarily unavailable.', 'bot');
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