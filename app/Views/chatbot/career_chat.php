<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <!-- Chat Interface -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-robot me-2"></i>Career Guidance Assistant
                    </h5>
                    <small>Let's discover your career goals and create a roadmap for success!</small>
                </div>
                
                <div class="card-body p-0">
                    <!-- Chat Messages -->
                    <div id="chat-messages" class="chat-container" style="height: 500px; overflow-y: auto; padding: 20px;">
                        <div class="message bot-message">
                            <div class="message-content">
                                <strong>Career Assistant:</strong> Hi there! 👋 I'm here to help you discover your career aspirations and turn them into achievable goals.
                                <br><br>
                                <strong>What do you want to be?</strong> Tell me about your dream career or what you'd like to achieve professionally.
                            </div>
                            <small class="text-muted">Just now</small>
                        </div>
                    </div>
                    
                    <!-- Chat Input -->
                    <div class="card-footer">
                        <form id="chat-form" class="d-flex">
                            <input type="hidden" id="session-id" value="">
                            <input type="text" id="chat-input" class="form-control me-2" 
                                   placeholder="Type your message here..." required>
                            <button type="submit" class="btn btn-primary" id="send-btn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Current Goals -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-bullseye me-2"></i>Your Goals</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($user_goals)): ?>
                        <?php foreach ($user_goals as $goal): ?>
                            <div class="goal-item mb-3 p-3 border rounded">
                                <h6 class="text-primary"><?= esc($goal['aspiration']) ?></h6>
                                <p class="small mb-2"><?= esc($goal['specific_goal']) ?></p>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar" style="width: <?= $goal['progress_percentage'] ?>%"></div>
                                </div>
                                <small class="text-muted"><?= $goal['progress_percentage'] ?>% complete</small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small">No goals set yet. Start chatting to create your first career goal!</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Mentor Suggestions -->
            <div id="mentor-suggestions" class="card shadow-sm" style="display: none;">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-user-tie me-2"></i>Recommended Mentors</h6>
                </div>
                <div class="card-body" id="mentor-list">
                    <!-- Mentors will be populated here -->
                </div>
            </div>
            
            <!-- SMART Goals Info -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>SMART Goals</h6>
                </div>
                <div class="card-body">
                    <div class="smart-goal-item mb-2">
                        <strong class="text-primary">S</strong>pecific - Clear and well-defined
                    </div>
                    <div class="smart-goal-item mb-2">
                        <strong class="text-success">M</strong>easurable - Trackable progress
                    </div>
                    <div class="smart-goal-item mb-2">
                        <strong class="text-info">A</strong>chievable - Realistic steps
                    </div>
                    <div class="smart-goal-item mb-2">
                        <strong class="text-warning">R</strong>elevant - Aligned with aspirations
                    </div>
                    <div class="smart-goal-item">
                        <strong class="text-danger">T</strong>ime-bound - Clear deadline
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-container {
    background: #f8f9fa;
}

.message {
    margin-bottom: 20px;
    max-width: 80%;
}

.user-message {
    margin-left: auto;
    text-align: right;
}

.user-message .message-content {
    background: #007bff;
    color: white;
    padding: 12px 16px;
    border-radius: 18px 18px 4px 18px;
    display: inline-block;
}

.bot-message .message-content {
    background: white;
    border: 1px solid #dee2e6;
    padding: 12px 16px;
    border-radius: 18px 18px 18px 4px;
    display: inline-block;
}

.mentor-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.mentor-card:hover {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0,123,255,0.1);
}

.goal-item {
    background: #f8f9fa;
}

.smart-goal-item {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.smart-goal-item:last-child {
    border-bottom: none;
}
</style>

<script>
class CareerChatbot {
    constructor() {
        this.sessionId = '';
        this.initializeChat();
    }
    
    initializeChat() {
        $('#chat-form').on('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
    }
    
    sendMessage() {
        const message = $('#chat-input').val().trim();
        if (!message) return;
        
        this.displayMessage(message, 'user');
        $('#chat-input').val('');
        
        $.post('/career-chatbot/chat', {
            message: message,
            session_id: this.sessionId
        })
        .done((response) => {
            this.handleResponse(response);
        });
    }
    
    handleResponse(response) {
        this.sessionId = response.session_id;
        this.displayMessage(response.response, 'bot');
        
        if (response.mentors && response.mentors.length > 0) {
            this.displayMentors(response.mentors);
        }
    }
    
    displayMessage(message, type) {
        const messageClass = type === 'user' ? 'user-message' : 'bot-message';
        const sender = type === 'user' ? 'You' : 'Career Assistant';
        
        const messageHtml = `
            <div class="message ${messageClass}">
                <div class="message-content">
                    ${type === 'bot' ? '<strong>' + sender + ':</strong> ' : ''}${message}
                </div>
                <small class="text-muted">Just now</small>
            </div>
        `;
        
        $('#chat-messages').append(messageHtml);
        $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
    }
    
    displayMentors(mentors) {
        let mentorHtml = '';
        
        mentors.forEach(mentor => {
            mentorHtml += `
                <div class="mentor-card">
                    <h6>${mentor.name}</h6>
                    <p class="text-muted small">${mentor.expertise}</p>
                    <div class="d-flex justify-content-between">
                        <span>₹${mentor.hourly_rate}/hr</span>
                        <button class="btn btn-sm btn-primary" onclick="window.location.href='/mentoring/book/${mentor.id}'">
                            Book Session
                        </button>
                    </div>
                </div>
            `;
        });
        
        $('#mentor-list').html(mentorHtml);
        $('#mentor-suggestions').show();
    }
}

$(document).ready(function() {
    new CareerChatbot();
});
</script>

<?= $this->endSection() ?>