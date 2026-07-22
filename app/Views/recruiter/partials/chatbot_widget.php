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
  background: linear-gradient(135deg, #0D8A90, #3F9E58);
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
  background: #53B86C;
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

.hm-chat-widget {
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
 
</style>

<div class="hirebot-widget" id="hirebotWidget">
  <div class="hirebot-tooltip" id="hirebotTooltip">
    Explore top career content
  </div>
  <button type="button" class="hirebot-fab" id="hmChatFab" aria-label="Open HireBot assistant">
    <span class="hirebot-fab-inner">
      <span class="hirebot-fab-text">Hire<br>Bot</span>
    </span>
  </button>
</div>

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
        <div class="hm-chat-header-title">HireBot AI</div>
        <button class="hm-chat-header-close" id="hmChatClose">&times;</button>
    </div>

    <div class="hm-chat-messages" id="hmChatMessages">
        <div class="hm-msg bot">
            <div class="hm-msg-bubble">
                Hi! I'm HireBot, your AI recruitment assistant. Ask me anything about your jobs, applications, candidates, or interviews.
            </div>
            <div class="hm-msg-time">Just now</div>
        </div>
        <div class="hm-chat-suggestions" id="hmChatSuggestions"></div>
    </div>

    <div class="hm-chat-input-wrap">
        <textarea class="hm-chat-input" id="hmChatInput" rows="1"
                  placeholder="Ask about your hiring data..." autocomplete="off"></textarea>
        <button class="hm-chat-voice" id="hmChatVoice" aria-label="Start voice chat" title="Start voice chat" type="button">
            <svg class="hm-chat-voice-mic" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
                <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                <line x1="12" y1="19" x2="12" y2="22"/>
            </svg>
            <svg class="hm-chat-voice-stop recruiter-hidden-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
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
    var briefUrl = baseUrl + '/recruiter/chatbot/brief';

    var isLoading  = false;
    var hasLoadedSuggestions = false;
    var hasLoadedBrief = false;
    var recognition = null;
    var isListening = false;
    var isSpeaking = false;
    var shouldSpeakNextReply = false;
    var voiceTurnSubmitted = false;
    var pendingDraft = null;
    var chatContext = {
        last_candidate: null,
        last_draft: null,
        last_job: null
    };

    hydratePageJobContext();

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
        if (!hasLoadedBrief) {
            loadMorningBrief();
        }
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
    function loadMorningBrief() {
        hasLoadedBrief = true;
        fetch(briefUrl, {
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
                if (!data || !data.success || !data.answer) return;
                updateChatContext(data.actions || []);
                appendMessage('bot', data.answer, data.actions || []);
            })
            .catch(function (err) {
                console.warn('HireBot: Could not load morning brief (' + err.message + ')');
            });
    }

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
                    var url = typeof item === 'object' && item ? (item.url || '') : '';
                    if (!text) return;
                    var btn = document.createElement('button');
                    btn.textContent = text;
                    btn.type = 'button';
                    btn.addEventListener('click', function () {
                        if (mode === 'link' && url) {
                            window.location.href = url;
                            return;
                        }
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
                console.warn('HireBot: Could not load suggestions (' + err.message + ')');
            });
    }

    // ── Send message ──
    function sendMessage(customText, options) {
        options = options || {};
        hydratePageJobContext();
        var inputText = input.value.trim();
        var text = (typeof customText === 'string' ? customText : inputText).trim();
        var displayText = options.displayText || text;
        if (typeof customText !== 'string' && pendingDraft && text && !looksLikeInternalCommand(text)) {
            displayText = text;
            text = pendingDraft.commandPrefix + text;
        }
        if (text === '' || isLoading) return;
        var silent = options.silent === true;

        input.value = '';
        autoResizeInput();
        pendingDraft = null;
        input.placeholder = 'Ask about your hiring data...';
        isLoading = true;
        sendBtn.disabled = true;
        if (voiceBtn && recognition) voiceBtn.disabled = true;

        if (!silent) {
            appendMessage('user', displayText);
        }

        // Show typing indicator
        var typingDiv = document.createElement('div');
        typingDiv.className = 'hm-msg bot';
        typingDiv.id = 'hmTypingIndicator';
        typingDiv.innerHTML = '<div class="hm-msg-bubble"><div class="hm-typing"><span></span><span></span><span></span></div></div>';
        msgBox.appendChild(typingDiv);
        scrollBottom();

        var formData = new FormData();
        formData.append('question', text);
        formData.append('context', JSON.stringify(chatContext));

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
                    updateChatContext(data.actions || []);
                    appendMessage('bot', data.answer, data.actions || []);
                    if (voiceBtn && recognition) voiceBtn.disabled = false;
                    speakRecruiterReply(data.answer);
                } else {
                    var reply = data.answer || 'Sorry, I couldn\'t process that. Please try asking differently.';
                    updateChatContext(data.actions || []);
                    appendMessage('bot', reply, data.actions || []);
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

    function appendMessage(role, text, actions) {
        var div = document.createElement('div');
        div.className = 'hm-msg ' + role;

        var bubble = document.createElement('div');
        bubble.className = 'hm-msg-bubble';
        bubble.textContent = text;
        div.appendChild(bubble);

        if (role === 'bot' && actions && actions.length) {
            div.appendChild(renderActionCards(actions));
        }

        var time = document.createElement('div');
        time.className = 'hm-msg-time';
        time.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        div.appendChild(time);

        msgBox.appendChild(div);
        if (role === 'bot' && actions && actions.length) {
            runAutomaticActions(actions);
        }
        requestAnimationFrame(scrollBottom);
    }

    function renderActionCards(actions) {
        var wrap = document.createElement('div');
        wrap.className = 'hm-action-cards';

        actions.forEach(function (card) {
            if (!card) return;

            var item = document.createElement('div');
            item.className = 'hm-action-card';

            var title = document.createElement('div');
            title.className = 'hm-action-card-title';
            title.textContent = card.title || 'Action';
            item.appendChild(title);

            if (card.meta) {
                var meta = document.createElement('div');
                meta.className = 'hm-action-card-meta';
                meta.textContent = card.meta;
                item.appendChild(meta);
            }

            if (card.detail) {
                var detail = document.createElement('div');
                detail.className = 'hm-action-card-detail';
                detail.textContent = card.detail;
                item.appendChild(detail);
            }

            if (card.items_title) {
                var itemsTitle = document.createElement('div');
                itemsTitle.className = 'hm-action-card-title hm-action-card-subtitle';
                itemsTitle.textContent = card.items_title;
                item.appendChild(itemsTitle);
            }

            if (card.items && card.items.length) {
                var list = document.createElement('div');
                list.className = 'hm-candidate-select-list';

                card.items.forEach(function (candidate) {
                    if (!candidate || !candidate.candidate_id) return;
                    var row = document.createElement('label');
                    row.className = 'hm-candidate-select-row';

                    var checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.value = String(candidate.candidate_id);
                    checkbox.setAttribute('data-candidate-id', String(candidate.candidate_id));
                    if (candidate.match_score !== undefined) {
                        checkbox.setAttribute('data-match-score', String(candidate.match_score));
                    }

                    var text = document.createElement('span');
                    text.className = 'hm-candidate-select-text';
                    var name = document.createElement('span');
                    name.className = 'hm-candidate-select-name';
                    name.textContent = candidate.label || 'Candidate';
                    text.appendChild(name);
                    if (candidate.meta) {
                        var meta = document.createElement('div');
                        meta.className = 'hm-candidate-select-meta';
                        meta.textContent = candidate.meta;
                        text.appendChild(meta);
                    }

                    row.appendChild(checkbox);
                    row.appendChild(text);
                    list.appendChild(row);
                });

                item.appendChild(list);
            }

            if (card.buttons && card.buttons.length) {
                var buttons = document.createElement('div');
                buttons.className = 'hm-action-buttons';

                card.buttons.forEach(function (action) {
                    if (!action || !action.label) return;
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'hm-action-btn ' + (action.kind || '');
                    btn.textContent = action.label;
                    btn.addEventListener('click', function () {
                        handleActionButton(action, item);
                    });
                    buttons.appendChild(btn);
                });

                item.appendChild(buttons);
            }

            wrap.appendChild(item);
        });

        return wrap;
    }

    function updateChatContext(actions) {
        if (!actions || !actions.length) return;

        actions.forEach(function (card) {
            if (!card) return;

            if ((card.type === 'candidate' || card.type === 'message_draft') && card.candidate_id) {
                chatContext.last_candidate = {
                    candidate_id: card.candidate_id,
                    application_id: card.application_id || null,
                    job_id: card.job_id || null,
                    candidate_name: card.candidate_name || card.title || '',
                    job_title: card.job_title || ''
                };
            }

            if (card.job_id || card.job_title) {
                chatContext.last_job = {
                    job_id: card.job_id || null,
                    job_title: card.job_title || card.meta || ''
                };
            }

            if (card.type === 'message_draft' && (card.command || card.subject || card.message_body)) {
                chatContext.last_draft = {
                    candidate_id: card.candidate_id || null,
                    application_id: card.application_id || null,
                    job_id: card.job_id || null,
                    subject: card.subject || '',
                    message_body: card.message_body || card.detail || '',
                    command: card.command || ''
                };
            }
        });
    }

    function handleActionButton(action, cardEl) {
        if (action.select_candidate_ids && cardEl) {
            var idMap = {};
            action.select_candidate_ids.forEach(function (id) {
                idMap[String(id)] = true;
            });
            cardEl.querySelectorAll('.hm-candidate-select-row input[type="checkbox"]').forEach(function (checkbox) {
                checkbox.checked = !!idMap[String(checkbox.value)];
            });
            return;
        }

        if (action.url) {
            if (action.kind === 'prefill_job' && action.job_prefill) {
                try {
                    window.sessionStorage.setItem('hmRecruiterJobPrefill', JSON.stringify(action.job_prefill));
                } catch (e) {}
            }
            if (action.kind === 'prefill_questions' && action.screening_prefill) {
                try {
                    window.sessionStorage.setItem('hmRecruiterScreeningPrefill', JSON.stringify(action.screening_prefill));
                } catch (e) {}
            }
            window.location.href = action.url;
            return;
        }

        if (action.requires_selection && cardEl) {
            var selectedIds = Array.prototype.slice.call(cardEl.querySelectorAll('.hm-candidate-select-row input[type="checkbox"]:checked'))
                .map(function (checkbox) { return checkbox.value; })
                .filter(Boolean);
            if (!selectedIds.length) {
                appendMessage('bot', 'Select at least one candidate first.');
                return;
            }
            var command = (action.command_prefix || '') + selectedIds.map(function (id) {
                return 'candidate #' + id;
            }).join(' ');
            sendMessage(command, { silent: action.silent === true });
            return;
        }

        if (action.kind === 'copy' || action.copy_text) {
            copyActionText(action.copy_text || action.command || '');
            return;
        }

        if (!action.command) return;

        rememberJobFromCommand(action.command, action.label || '');

        if (action.kind === 'draft') {
            var draftText = typeof action.draft_text === 'string' ? action.draft_text : extractDraftText(action.command);
            var commandPrefix = Object.prototype.hasOwnProperty.call(action, 'command_prefix')
                ? action.command_prefix
                : extractCommandPrefix(action.command);
            pendingDraft = {
                commandPrefix: commandPrefix,
                originalCommand: action.command
            };
            input.value = draftText;
            input.placeholder = 'Edit the message, then press Send';
            autoResizeInput();
            input.focus();
            if (input.setSelectionRange) {
                input.setSelectionRange(input.value.length, input.value.length);
            }
            return;
        }

        sendMessage(action.command, { silent: action.silent === true });
    }

    function looksLikeInternalCommand(value) {
        return /^(confirm\s+)?(send|message|shortlist|reject)\b/i.test(String(value || ''));
    }

    function extractCommandPrefix(command) {
        var value = String(command || '');
        var colon = value.indexOf(':');
        return colon >= 0 ? value.slice(0, colon + 1) + ' ' : value + ' ';
    }

    function extractDraftText(command) {
        var value = String(command || '');
        var colon = value.indexOf(':');
        return colon >= 0 ? value.slice(colon + 1).trim() : value;
    }

    function autoResizeInput() {
        if (!input) return;
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    }

    function copyActionText(text) {
        var value = String(text || '').trim();
        if (!value) return;

        function showCopied() {
            appendMessage('bot', 'Copied.');
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(showCopied).catch(function () {
                fallbackCopy(value);
                showCopied();
            });
            return;
        }

        fallbackCopy(value);
        showCopied();
    }

    function fallbackCopy(value) {
        var area = document.createElement('textarea');
        area.value = value;
        area.setAttribute('readonly', 'readonly');
        area.style.position = 'fixed';
        area.style.left = '-9999px';
        document.body.appendChild(area);
        area.select();
        try {
            document.execCommand('copy');
        } catch (e) {}
        document.body.removeChild(area);
    }

    function hydratePageJobContext() {
        var source = document.querySelector('[data-job-id][data-job-title]');
        var jobId = 0;
        var jobTitle = '';

        if (source) {
            jobId = parseInt(source.getAttribute('data-job-id') || '0', 10) || 0;
            jobTitle = String(source.getAttribute('data-job-title') || '').trim();
        }

        if (!jobId) {
            var match = window.location.pathname.match(/\/(?:applications\/job|jobs\/responses|jobs)\/(\d+)\b/i);
            if (match) {
                jobId = parseInt(match[1], 10) || 0;
            }
        }

        if (!jobTitle) {
            var titleEl = document.querySelector('.recruiter-pipeline-page .page-board-title, .page-board-title');
            if (titleEl) {
                jobTitle = String(titleEl.textContent || '').trim();
            }
        }

        if (jobId || jobTitle) {
            chatContext.last_job = {
                job_id: jobId || null,
                job_title: jobTitle
            };
        }
    }

    function rememberJobFromCommand(command, label) {
        var match = String(command || '').match(/\bjob\s*#?\s*(\d+)\b/i);
        if (!match) return;
        chatContext.last_job = {
            job_id: parseInt(match[1], 10),
            job_title: String(label || '').replace(/\s+-\s+.*$/, '')
        };
    }

    function runAutomaticActions(actions) {
        actions.forEach(function (card) {
            if (!card || !card.auto_download || !card.download_url || card._downloadStarted) return;
            card._downloadStarted = true;
            var link = document.createElement('a');
            link.href = card.download_url;
            link.download = '';
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            window.setTimeout(function () {
                if (link.parentNode) link.parentNode.removeChild(link);
            }, 1000);
        });
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
            if (e.shiftKey) {
                return;
            }
            e.preventDefault();
            sendMessage();
        }
    });
    input.addEventListener('input', autoResizeInput);

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && widget.classList.contains('open')) {
            closeWidget();
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
