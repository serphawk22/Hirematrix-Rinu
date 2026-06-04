        <?= view('Layouts/candidate_header', ['title' => 'AI Career Mentor']) ?>

<div class="career-transition-jobboard">
    <section class="career-transition-content">
        <div class="container">
            <div class="page-board-header page-board-header-tight">
                <div class="page-board-copy">
                    <span class="page-board-kicker"><i class="fas fa-crown"></i> <?= esc($subscription['plan_name']) ?></span>
                    <h1 class="page-board-title">AI Career Mentor</h1>
                    <p class="page-board-subtitle">Your personal AI career coach — active until <?= date('M d, Y', strtotime($subscription['end_date'])) ?></p>
                </div>
                <div class="page-board-actions">
                    <a href="<?= base_url('premium/plans?service=mentor') ?>" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-up mr-1"></i> Upgrade Plan
                    </a>
                </div>
            </div>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= esc(session()->getFlashdata('success')) ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <div class="career-transition-simple-layout">

                <!-- Chat Panel -->
                <div class="career-transition-card dashboard-panel">
                    <div class="panel-header">
                        <h2 class="section-title mb-1"><i class="fas fa-robot mr-2"></i>Chat with Your AI Mentor</h2>
                        <p class="section-subtitle mb-0">
                            <?php if ($subscription['chat_limit']): ?>
                                <?= $usage_today ?>/<?= $subscription['chat_limit'] ?> chats used today
                            <?php else: ?>
                                Unlimited chats available
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="panel-body">

                        <!-- Quick Actions -->
                        <div class="mb-3 d-flex flex-wrap align-items-center">
    <button class="btn btn-sm btn-outline-primary me-2 mb-2"
        onclick="quickChat('Help me create a career plan to become a <?= esc($active_sessions[0]['target_role'] ?? 'Software Engineer') ?>')">
        <i class="fas fa-route me-1"></i> Career Plan
    </button>
    &#160;
    <button class="btn btn-sm btn-outline-success me-2 mb-2"
        onclick="quickChat('Do a skill gap analysis for my target role')">
        <i class="fas fa-chart-bar me-1"></i> Skill Gap
    </button>
    &#160;
    <button class="btn btn-sm btn-outline-info me-2 mb-2"
        onclick="quickChat('Help me prepare for interviews')">
        <i class="fas fa-microphone me-1"></i> Interview Prep
    </button>
    &#160;
    <button class="btn btn-sm btn-outline-warning me-2 mb-2"
        onclick="quickChat('Review and optimize my resume')">
        <i class="fas fa-file-alt me-1"></i> Resume Review
    </button>
    &#160;
    <button class="btn btn-sm btn-outline-danger me-2 mb-2"
        onclick="quickChat('Give me salary negotiation tips')">
        <i class="fas fa-dollar-sign me-1"></i> Salary Tips
    </button>

</div>

                        <!-- Chat Messages -->
                        <div id="chat-messages" style="height: 400px; overflow-y: auto; background: #f8f9fa; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                            <div class="chat-bot-msg mb-3">
                                <div style="background: white; border: 1px solid #dee2e6; padding: 12px 16px; border-radius: 18px 18px 18px 4px; display: inline-block; max-width: 85%;">
                                    <strong>AI Career Mentor:</strong> Welcome! 🚀 I'm your personal AI career coach.<br><br>
                                    I can help you with career planning, skill development, interview preparation, resume optimization, and salary negotiation.<br><br>
                                    <strong>What would you like to work on today?</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Chat Input -->
                        <div class="chat-input-area mt-4">
                            <?php if (!empty($active_sessions)): ?>
                                <?php
                                    $s = $active_sessions[0];
                                    $nextMilestones = array_slice($s['next_milestones'] ?? [], 0, 3);
                                    $roleName = trim((string) ($s['target_role'] ?? 'career'));
                                    $lastNudge = trim((string) ($s['last_nudge'] ?? ''));
                                    $milestone = !empty($nextMilestones) ? $nextMilestones[0] : null;

                                    if ($milestone) {
                                        $continuePrompt = "I'm ready to work on '{$milestone}'. Any tips on getting started?";
                                    } elseif ($lastNudge !== '' && mb_strlen($lastNudge) < 100) {
                                        $continuePrompt = "Regarding your advice \"{$lastNudge}\"—what's the best next step?";
                                    } else {
                                        $continuePrompt = "I'm ready to keep moving on my {$roleName} goal. What's the next step?";
                                    }
                                    $planSessionDomId = 'plan-' . (int) ($s['id'] ?? 0);
                                ?>
                                <div class="mb-2" id="smart-prompt-wrapper">
                                    <button type="button" class="btn btn-sm btn-light border text-primary" 
                                            id="continue-plan-magic-btn"
                                            data-plan-session-id="<?= esc($planSessionDomId, 'attr') ?>"
                                            data-plan-title="<?= esc($s['target_role'], 'attr') ?>"
                                            data-continue-plan-prompt="<?= esc($continuePrompt, 'attr') ?>"
                                            style="border-radius: 20px; font-size: 11px; padding: 5px 14px; font-weight: 600; box-shadow: 0 2px 5px rgba(0,0,0,0.04);">
                                        <i class="fas fa-magic mr-1 text-warning"></i> <span><?= esc($continuePrompt) ?></span>
                                    </button>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex">
                                <input type="text" id="chat-input" class="form-control mr-2"
                                       placeholder="Ask your AI career mentor anything...">
                                <button type="button" id="chat-send" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div>
                    <!-- Plan Features -->
                    <div class="dashboard-panel">
                        <div class="panel-header">
                            <h5 class="section-title mb-0"><i class="fas fa-star mr-2"></i>Your Plan Features</h5>
                        </div>
                        <div class="panel-body">
                            <?php foreach (json_decode($subscription['features'], true) as $feature): ?>
                                <div class="mb-2 small">
                                    <i class="fas fa-check text-success mr-2"></i><?= esc($feature) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
</div>

<?= view('Layouts/candidate_footer') ?>

<script>
var chatSessionId = '';
var chatUrl = '<?= base_url('premium-mentor/chat') ?>';

function getCsrfData() {
    return {
        name: '<?= csrf_token() ?>',
        hash: '<?= csrf_hash() ?>'
    };
}

function formatFeatureLabel(feature) {
    return feature.replace(/_/g, ' ').replace(/\b\w/g, function(letter) {
        return letter.toUpperCase();
    });
}

function displayMessage(message, type, premiumFeatures) {
    var isUser = (type === 'user');
    var bubble = $('<div>').css({
        background: isUser ? '#007bff' : '#fff',
        color: isUser ? '#fff' : 'inherit',
        border: '1px solid ' + (isUser ? '#007bff' : '#dee2e6'),
        padding: '12px 16px',
        borderRadius: isUser ? '18px 18px 4px 18px' : '18px 18px 18px 4px',
        display: 'inline-block',
        maxWidth: '85%',
        textAlign: 'left',
        whiteSpace: 'pre-wrap'
    });
    if (!isUser) {
        bubble.html('<strong>AI Career Mentor:</strong> ' + message);
    } else {
        bubble.text(message);
    }

    if (!isUser && Array.isArray(premiumFeatures) && premiumFeatures.length) {
        var featureWrap = $('<div>').css({
            marginTop: '10px',
            display: 'flex',
            flexWrap: 'wrap',
            gap: '6px'
        });

        premiumFeatures.forEach(function(feature) {
            $('<span>')
                .text(formatFeatureLabel(feature))
                .css({
                    fontSize: '11px',
                    background: '#eef6ff',
                    color: '#0b5ed7',
                    border: '1px solid #bfd8ff',
                    borderRadius: '999px',
                    padding: '3px 8px',
                    display: 'inline-block'
                })
                .appendTo(featureWrap);
        });

        bubble.append(featureWrap);
    }

    var wrapper = $('<div>').addClass('mb-3').css('textAlign', isUser ? 'right' : 'left').append(bubble);
    $('#chat-messages').append(wrapper);
    $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
}

function showTyping() {
    var bubble = $('<div>').css({
        background: '#fff', border: '1px solid #dee2e6',
        padding: '12px 16px', borderRadius: '18px 18px 18px 4px',
        display: 'inline-block'
    }).html('<em>Typing...</em>');
    $('<div>').attr('id', 'typing-indicator').addClass('mb-3').append(bubble).appendTo('#chat-messages');
    $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
}

function hideTyping() { $('#typing-indicator').remove(); }

function sendMessage() {
    var message = $('#chat-input').val().trim();
    if (!message) return;

    displayMessage(message, 'user');
    $('#chat-input').val('');
    showTyping();
    $('#chat-send').prop('disabled', true);

    var csrf = getCsrfData();
    var postData = { message: message, session_id: chatSessionId };
    postData[csrf.name] = csrf.hash;

    $.ajax({
        url: chatUrl,
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function(res) {
            hideTyping();
            $('#chat-send').prop('disabled', false);
            if (res && res.message) {
                chatSessionId = res.session_id || chatSessionId;
                displayMessage(res.message, 'bot', res.premium_features || []);
                updatePlanCard(chatSessionId, res.progress_tracking || null);
            } else if (res && res.error) {
                displayMessage(res.error, 'bot');
            } else {
                displayMessage('No response received. Please try again.', 'bot');
            }
        },
        error: function(xhr) {
            hideTyping();
            $('#chat-send').prop('disabled', false);
            displayMessage('Error ' + xhr.status + ': ' + (xhr.responseText ? xhr.responseText.substring(0, 100) : 'Unknown error'), 'bot');
        }
    });
}

function quickChat(message) {
    $('#chat-input').val(message);
    sendMessage();
}

function updatePlanCard(sessionId, tracking) {
    if (!tracking) return;

    // Use tracking.plan_card_id if provided by the controller, otherwise fallback to sessionId
    var targetId = tracking.plan_card_id || sessionId;
    if (!targetId) return;

    var progress = parseInt(tracking.progress_percentage, 10);

    // Update the "Magic" smart follow-up chip above the chat input
    var magicBtn = $('#continue-plan-magic-btn');
    if (magicBtn.length) {
        var nextStep = (tracking.next_milestones && tracking.next_milestones.length) ? tracking.next_milestones[0] : null;
        var updatedPrompt = '';

        if (nextStep) {
            updatedPrompt = "I'm ready to work on '" + nextStep + "'. Any tips on getting started?";
        } else if (tracking.last_nudge && tracking.last_nudge.length < 100) {
            updatedPrompt = "Regarding your advice \"" + tracking.last_nudge + "\"—what's the best next step?";
        } else {
            updatedPrompt = "I'm ready to keep moving forward. What's the best next action for me?";
        }
            
        magicBtn.data('continue-plan-prompt', updatedPrompt).attr('data-continue-plan-prompt', updatedPrompt);
        magicBtn.find('span').text(updatedPrompt);
    }

    var card = $('[data-plan-card="' + String(targetId).replace(/"/g, '\\"') + '"]');
    if (!card.length) return;

    if (!isNaN(progress)) {
        progress = Math.max(0, Math.min(100, progress));
        card.find('.js-plan-progress-label').text(progress + '%');
        card.find('.js-plan-progress-bar').css('width', progress + '%');
    }

    if (Array.isArray(tracking.next_milestones) && tracking.next_milestones.length) {
        var milestonesText = tracking.next_milestones.slice(0, 3).join(' | ');
        var milestones = card.find('.js-plan-milestones');
        if (!milestones.length) {
            milestones = $('<div class="small mt-2 js-plan-milestones" style="color: #475569; line-height: 1.45;"><strong>Next milestones:</strong> <span></span></div>');
            card.find('.progress').after(milestones);
        }
        milestones.find('span').text(milestonesText);
        milestones.removeClass('d-none');
    }

    if (tracking.last_nudge) {
        card.find('.js-plan-nudge').text(tracking.last_nudge).removeClass('d-none');
    }
}

$(document).ready(function() {
    $('#chat-send').on('click', function() {
        sendMessage();
    });

    $('#chat-input').on('keypress', function(e) {
        if (e.which === 13) { sendMessage(); }
    });

    $('[data-continue-plan-prompt]').on('click', function() {
        var newSessionId = String($(this).data('plan-session-id') || '');
        var planTitle = $(this).data('plan-title') || 'Active Plan';
        
        // Clear previous conversation when switching contexts to "remove the old"
        if (newSessionId && chatSessionId !== newSessionId) {
            $('#chat-messages').html('<div class="text-center my-3"><span class="badge badge-light px-3 py-2" style="color: #64748b; border: 1px solid #e2e8f0; font-weight: 500; border-radius: 99px;"><i class="fas fa-sync-alt mr-1"></i> Context switched: ' + planTitle + '</span></div>');
            chatSessionId = newSessionId;
        }

        var prompt = $(this).data('continue-plan-prompt') || 'Continue my career plan';
        quickChat(prompt);
    });
});
</script>
    
