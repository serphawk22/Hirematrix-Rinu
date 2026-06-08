        <?= view('Layouts/candidate_header', ['title' => $title ?? 'Premium Services Plans - HireMatrix']) ?>

<?php
$selectedService = strtolower((string) ($selected_service ?? 'all'));
$serviceCards = [
    [
        'key' => 'career-transition',
        'title' => 'Career Transition AI',
        'icon' => 'fas fa-map-signs',
        'accent' => 'primary',
        'summary' => 'Build a structured learning path from your current role to your target role.',
        'points' => [
            'Personalized roadmap',
            'Daily actionable tasks',
            'Skill gap analysis',
            'Course modules and exercises',
        ],
    ],
    [
        'key' => 'resume-studio',
        'title' => 'Resume Studio',
        'icon' => 'fas fa-file-alt',
        'accent' => 'success',
        'summary' => 'Create AI-assisted resume versions for roles, jobs, and career pivots.',
        'points' => [
            'ATS-friendly resumes',
            'Job-specific versions',
            'Career transition resumes',
            'Unlimited updates',
        ],
    ],
    [
        'key' => 'mentor',
        'title' => 'AI Career Mentor',
        'icon' => 'fas fa-comments',
        'accent' => 'info',
        'summary' => 'Chat with a career mentor for interview prep, strategy, and next-step guidance.',
        'points' => [
            'Unlimited mentor chats',
            'Interview preparation',
            'Resume review guidance',
            'Job search strategy',
        ],
    ],
];
?>

<div class="career-transition-jobboard premium-plans-jobboard">
    <section class="career-transition-content">
        <div class="container-fluid px-lg-5">
            <div class="page-board-header page-board-header-tight">
                <div class="page-board-copy">
                    <span class="page-board-kicker"><i class="fas fa-crown"></i> Premium Services</span>
                    <h1 class="page-board-title">One subscription unlocks all three AI services</h1>
                    <p class="page-board-subtitle">Unlock Career Transition AI, Resume Studio, and AI Career Mentor from one shared plans page.</p>
                </div>
            </div>

            <div class="premium-plans-body">
            <?php if ($selectedService !== 'all'): ?>
                <div class="alert alert-info border-0 shadow-sm mb-4">
                    You came here from <strong><?= esc(ucwords(str_replace('-', ' ', $selectedService))) ?></strong>.
                    This plan page covers all premium services in one place.
                </div>
            <?php endif; ?>

            <?php if (!empty($current_subscription)): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4">
                    Your subscription is active. You can open the premium services directly from the Services menu.
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= esc(session()->getFlashdata('success')) ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <div class="row mb-5">
                <?php foreach ($serviceCards as $card): ?>
                    <?php $isSelected = $selectedService === $card['key']; ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="dashboard-panel h-100 premium-service-card <?= $isSelected ? 'is-selected' : '' ?>">
                            <?php if ($isSelected): ?>
                                <div class="premium-service-ribbon">
                                    <small><i class="fas fa-star"></i> Selected service</small>
                                </div>
                            <?php else: ?>
                                <div class="premium-service-ribbon premium-service-ribbon-placeholder" aria-hidden="true"></div>
                            <?php endif; ?>
                            <div class="panel-body p-4">
                                <div class="mb-3 text-center">
                                    <i class="<?= esc($card['icon']) ?> fa-3x text-<?= esc($card['accent']) ?>"></i>
                                </div>
                                <h4 class="mb-3 text-center"><?= esc($card['title']) ?></h4>
                                <p class="text-muted small mb-3"><?= esc($card['summary']) ?></p>

                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($card['points'] as $point): ?>
                                        <li class="mb-2 small">
                                            <i class="fas fa-check text-success mr-2"></i><?= esc($point) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row mb-5">
                <div class="col-12 text-center mb-4">
                    <h3>Choose a plan to unlock every premium service</h3>
                    <p class="text-muted mb-0">Your subscription works across Career Transition AI, Resume Studio, and AI Career Mentor.</p>
                </div>

                <?php 
                // Find a basis plan for the trial (usually the first paid plan)
                $trialBasePlanId = 0;
                foreach($plans as $p) {
                    if ((float)$p['price'] > 0) {
                        $trialBasePlanId = (int)$p['id'];
                        break;
                    }
                }
                ?>

                <?php if (empty($current_subscription) && isset($trial_days) && $trial_days > 0 && !$has_used_trial): ?>
                    <!-- Dedicated Free Trial Card -->
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="dashboard-panel h-100 premium-plan-card premium-trial-card">
                            <div class="text-center py-2 bg-primary text-white premium-plan-ribbon">
                                <small><i class="fas fa-gift"></i> Limited Time Offer</small>
                            </div>
                            <div class="panel-body p-4 premium-plan-body">
                                <h4 class="mb-3 text-center premium-plan-title">7-Day Free Trial</h4>
                                <div class="mb-3 text-center premium-plan-price">
                                    <span class="h2 text-primary font-weight-bold">Free</span>
                                    <small class="text-muted d-block">Full Access for <?= (int) $trial_days ?> Days</small>
                                </div>
                                <p class="text-muted small mb-3 premium-plan-description">Experience every premium feature including AI Mentor and Resume Studio before you commit.</p>
                                <ul class="list-unstyled mb-4 premium-plan-features">
                                    <li class="mb-2 small"><i class="fas fa-check text-success mr-2"></i>All AI Services</li>
                                    <li class="mb-2 small"><i class="fas fa-check text-success mr-2"></i>No payment needed</li>
                                    <li class="mb-2 small"><i class="fas fa-check text-success mr-2"></i>Cancel anytime</li>
                                </ul>
                                <form action="<?= base_url('premium-mentor/start-trial') ?>" method="post" class="premium-plan-action">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="plan_id" value="<?= $trialBasePlanId ?>">
                                    <button type="submit" class="btn btn-outline-primary btn-block">Start Free Trial</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($plans as $plan): ?>
                    <?php
                    $planFeatures = json_decode((string) ($plan['features'] ?? '[]'), true) ?: [];
                    $isPopular = strcasecmp((string) ($plan['name'] ?? ''), 'Pro Monthly') === 0;
                    ?>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="dashboard-panel h-100 premium-plan-card <?= $isPopular ? 'border border-primary' : '' ?>">
                            <?php if ($isPopular): ?>
                                <div class="text-center py-2 bg-primary text-white premium-plan-ribbon">
                                    <small><i class="fas fa-star"></i> Most Popular</small>
                                </div>
                            <?php else: ?>
                                <div class="premium-plan-ribbon premium-plan-ribbon-placeholder" aria-hidden="true"></div>
                            <?php endif; ?>
                            <div class="panel-body p-4 premium-plan-body">
                                <h4 class="mb-3 text-center premium-plan-title"><?= esc($plan['name']) ?></h4>
                                <div class="mb-3 text-center premium-plan-price">
                                    <?php if ((float) $plan['price'] <= 0): ?>
                                        <span class="h2 text-success font-weight-bold">Free</span>
                                    <?php else: ?>
                                        <span class="h2 text-primary font-weight-bold">₹<?= number_format((float) $plan['price']) ?></span>
                                        <small class="text-muted d-block">
                                            /<?= (int) $plan['duration_days'] === 30 ? 'month' : ((int) $plan['duration_days'] === 90 ? 'quarter' : 'year') ?>
                                        </small>
                                    <?php endif; ?>
                                </div>

                                <p class="text-muted small mb-3 premium-plan-description"><?= esc((string) ($plan['description'] ?? '')) ?></p>

                                <ul class="list-unstyled mb-4 premium-plan-features">
                                    <?php foreach ($planFeatures as $feature): ?>
                                        <li class="mb-2 small">
                                            <i class="fas fa-check text-success mr-2"></i><?= esc((string) $feature) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="premium-plan-action">
                                <?php if ((float) $plan['price'] <= 0): ?>
                                    <a href="<?= base_url('candidate/dashboard') ?>" class="btn btn-outline-primary btn-block">Get Started Free</a>
                                <?php else: ?>
                                    <button type="button"
                                        class="btn btn-primary btn-block js-pay-btn"
                                        data-plan-id="<?= (int) $plan['id'] ?>"
                                        data-plan-name="<?= esc($plan['name']) ?>"
                                        data-amount="<?= (int) round((float) $plan['price'] * 100) ?>"
                                        data-price="<?= esc(number_format((float) $plan['price'])) ?>">
                                        Subscribe &#8377;<?= number_format((float) $plan['price']) ?>
                                    </button>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>



            <div class="text-center mt-5">
                <p class="mt-3 text-muted">
                    One subscription unlocks all three services. Cancel anytime.
                </p>
            </div>
            </div>
        </div>
    </section>
</div>

<?= view('Layouts/candidate_footer') ?>

<!-- Razorpay Checkout -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.querySelectorAll('.js-pay-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var planId   = this.dataset.planId;
        var planName = this.dataset.planName;
        var self     = this;

        self.disabled = true;
        self.textContent = 'Processing...';

        // Step 1: create Razorpay order on server
        fetch('<?= base_url('payment/create-order') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'plan_id=' + planId + '&<?= csrf_token() ?>=' + '<?= csrf_hash() ?>'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) {
                alert(data.error);
                self.disabled = false;
                self.textContent = 'Subscribe \u20B9' + self.dataset.price;
                return;
            }

            // Step 2: open Razorpay checkout modal
            var options = {
                key:         data.key_id,
                amount:      data.amount,
                currency:    data.currency,
                name:        'HireMatrix',
                description: planName,
                order_id:    data.order_id,
                theme:       { color: '#1FB7B5' },
                handler: function(response) {
                    // Step 3: verify payment on server
                    fetch('<?= base_url('payment/verify') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: [
                            'razorpay_order_id='   + encodeURIComponent(response.razorpay_order_id),
                            'razorpay_payment_id=' + encodeURIComponent(response.razorpay_payment_id),
                            'razorpay_signature='  + encodeURIComponent(response.razorpay_signature),
                            '<?= csrf_token() ?>=' + '<?= csrf_hash() ?>'
                        ].join('&')
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(result) {
                        if (result.success) {
                            window.location.href = result.redirect;
                        } else {
                            alert(result.error || 'Payment verification failed. Please contact support.');
                            self.disabled = false;
                            self.textContent = 'Subscribe \u20B9' + self.dataset.price;
                        }
                    });
                },
                modal: {
                    ondismiss: function() {
                        self.disabled = false;
                        self.textContent = 'Subscribe \u20B9' + self.dataset.price;
                    }
                }
            };

            var rzp = new Razorpay(options);
            rzp.open();
        })
        .catch(function() {
            alert('Something went wrong. Please try again.');
            self.disabled = false;
            self.textContent = 'Subscribe \u20B9' + self.dataset.price;
        });
    });
});
</script>
    
