<?= view('Layouts/candidate_header', ['title' => 'My Interview Bookings']) ?>

<div class="my-bookings-jobboard">
    <div class="container">
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-calendar-check"></i> Interview calendar</span>
                <h1 class="page-board-title">My Interview Bookings</h1>
                <p class="page-board-subtitle">Review upcoming interviews, track completed bookings, and reschedule when needed.</p>
            </div>
            
        </div>
    </div>

    <section class="site-section pt-0 content-wrap">
        <div class="container">
            <?php
            $bookings = $bookings ?? [];
            usort($bookings, static function (array $a, array $b): int {
                return strtotime((string) ($a['slot_datetime'] ?? '')) <=> strtotime((string) ($b['slot_datetime'] ?? ''));
            });

            $tz = new \DateTimeZone('Asia/Kolkata');
            $now = new \DateTime('now', $tz);

            $upcomingBookings = array_values(array_filter($bookings, static function (array $booking) use ($tz, $now): bool {
                return new \DateTime($booking['slot_datetime'], $tz) >= $now;
            }));
            $pastBookings = array_values(array_filter($bookings, static function (array $booking) use ($tz, $now): bool {
                return new \DateTime($booking['slot_datetime'], $tz) < $now;
            }));
            $nextBooking = $upcomingBookings[0] ?? null;
            $upcomingCount = count($upcomingBookings);
            $completedCount = count(array_filter($pastBookings, static function (array $booking): bool {
                return ($booking['booking_status'] ?? '') === 'completed';
            }));
            ?>

            <div class="bookings-summary-row">
                <span class="summary-chip">Total: <?= count($bookings) ?></span>
                <span class="summary-chip">Upcoming: <?= $upcomingCount ?></span>
                <span class="summary-chip">Completed: <?= $completedCount ?></span>
            </div>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('success') ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> <?= session()->getFlashdata('error') ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <?php if (empty($bookings)): ?>
                <div class="card booking-empty-card text-center">
                    <div class="card-body py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <h5 class="mb-2">No Interview Bookings</h5>
                        <p class="text-muted mb-4">You do not have any scheduled interviews yet.</p>
                        <a href="<?= base_url('candidate/dashboard') ?>" class="btn btn-primary">
                            Go to Dashboard
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php if (!empty($nextBooking)): ?>
                    <?php
                $target = new \DateTime($nextBooking['slot_datetime'], $tz);
                $isPast = $now > $target;

                $interval = $now->diff($target);
                $nextDays = $interval->days;
                $nextHours = $interval->h;
                $nextMins = $interval->i;

                $nextCanReschedule = $nextBooking['reschedule_count'] < $nextBooking['max_reschedules']
                    && ($target->getTimestamp() - $now->getTimestamp()) > 86400; // 24 hours in seconds
                ?>
                    <div class="booking-next-step">
                        <div class="booking-next-step-copy">
                            <span class="booking-next-step-kicker">Next action</span>
                            <h3><?= esc($nextBooking['job_title']) ?></h3>
                            <p>
                                <?= date('l, F j, Y', strtotime($nextBooking['slot_datetime'])) ?> at <?= date('h:i A', strtotime($nextBooking['slot_datetime'])) ?>.
                                <?php if ($isPast): ?>
                                    <span class="text-danger font-weight-bold">This interview has already started.</span>
                                <?php elseif ($nextDays > 0): ?>
                                    You have <?= $nextDays ?>d <?= $nextHours ?>h left to prepare.
                                <?php elseif ($nextHours > 0): ?>
                                    You have <?= $nextHours ?>h <?= $nextMins ?>m left to prepare.
                                <?php else: ?>
                                    You have <?= $nextMins ?> minute(s) left. Starting soon!
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="booking-next-step-actions">
                            <?php if ($nextCanReschedule): ?>
                                <a href="<?= base_url('candidate/reschedule-slot/' . $nextBooking['application_id']) ?>" class="btn btn-warning">
                                    <i class="fas fa-sync mr-1"></i>Reschedule now
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-ban mr-1"></i>Reschedule closed
                                </button>
                            <?php endif; ?>
                            <a href="<?= base_url('candidate/dashboard') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-home mr-1"></i>Open dashboard
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="booking-timeline">
                    <?php foreach ($upcomingBookings as $booking): ?>
                        <?php
                        $targetBooking = new \DateTime($booking['slot_datetime'], $tz);
                        $intervalBooking = $now->diff($targetBooking);
                        $days = $intervalBooking->days;
                        $hours = $intervalBooking->h;
                        $mins = $intervalBooking->i;
                        $diffSeconds = $targetBooking->getTimestamp() - $now->getTimestamp();
                        $canReschedule = $booking['reschedule_count'] < $booking['max_reschedules'] && $diffSeconds > 86400;
                        $statusColors = [
                            'booked' => 'primary',
                            'rescheduled' => 'warning',
                            'completed' => 'success',
                            'no_show' => 'danger',
                            'cancelled' => 'danger'
                        ];
                        $statusLabels = [
                            'booked' => 'Booked',
                            'rescheduled' => 'Rescheduled',
                            'completed' => 'Completed',
                            'no_show' => 'No Show',
                            'cancelled' => 'Cancelled'
                        ];
                        $statusColor = $statusColors[$booking['booking_status']] ?? 'secondary';
                        $candidateStatus = $statusLabels[$booking['booking_status']] ?? ucwords(str_replace('_', ' ', (string) $booking['booking_status']));
                        $statusSummary = $booking['booking_status'] === 'completed'
                            ? 'Your interview is complete and the next update will appear here when the team reviews it.'
                            : ($canReschedule
                                ? 'Your slot is confirmed. You can still reschedule if your plans change.'
                                : 'Your slot is locked, so the best next step is preparation.');
                        $statusNextStep = $booking['booking_status'] === 'completed'
                            ? 'Await the recruiter update in your booking history.'
                            : ($canReschedule
                                ? 'Reschedule only if you need a different time.'
                                : 'Review the role details and prepare for the call.');
                        ?>
                        <article class="booking-timeline-item is-upcoming<?= isset($nextBooking) && $nextBooking && $nextBooking['application_id'] === $booking['application_id'] ? ' is-next' : '' ?>">
                            <div class="booking-timeline-marker">
                                <span>Next</span>
                            </div>
                            <div class="booking-timeline-card">
                                <div class="booking-timeline-head">
                                    <div>
                                        <div class="booking-timeline-title-row">
                                            <h5 class="mb-0">
                                                <i class="fas fa-briefcase mr-2"></i><?= esc($booking['job_title']) ?>
                                            </h5>
                                            <span class="badge badge-<?= $statusColor ?> booking-status-badge">
                                                <?= esc($statusLabels[$booking['booking_status']] ?? ucwords(str_replace('_', ' ', $booking['booking_status']))) ?>
                                            </span>
                                        </div>
                                        <p class="booking-timeline-meta mb-0">
                                            <?= date('l, F j, Y', strtotime($booking['slot_datetime'])) ?> at <?= date('h:i A', strtotime($booking['slot_datetime'])) ?>
                                        </p>
                                    </div>
                                    <div class="booking-timeline-pill">
                                        <?php if ($days > 0): ?>
                                            <?= $days ?>d <?= $hours ?>h left
                                        <?php elseif ($hours > 0): ?>
                                            <?= $hours ?>h <?= $mins ?>m left
                                        <?php else: ?>
                                            <?= $mins ?>m left
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="booking-timeline-body">
                                    <div class="booking-timeline-column">
                                        <span class="booking-timeline-label">What to do</span>
                                        <p class="booking-timeline-text">
                                            <?= $canReschedule ? 'Confirm this slot, or reschedule if you need more time.' : 'Focus on preparation because rescheduling is no longer available.' ?>
                                        </p>
                                        <ul class="booking-timeline-checklist">
                                            <li>Review the job description and match your answers to it.</li>
                                            <li>Prepare a short introduction and 2 to 3 role examples.</li>
                                            <li>Check your time zone and join details before the interview.</li>
                                        </ul>
                                    </div>
                                    <div class="booking-timeline-column">
                                        <span class="booking-timeline-label">Booking details</span>
                                        <ul class="booking-timeline-facts">
                                            <li><strong>Booked on:</strong> <?= date('M j, Y', strtotime($booking['booked_at'])) ?></li>
                                            <li><strong>Reschedules:</strong> <?= $booking['reschedule_count'] ?> / <?= $booking['max_reschedules'] ?></li>
                                            <?php if ($booking['last_rescheduled_at']): ?>
                                                <li><strong>Last rescheduled:</strong> <?= date('M j, Y h:i A', strtotime($booking['last_rescheduled_at'])) ?></li>
                                            <?php endif; ?>
                                        </ul>

                                        <div class="booking-status-card">
                                            <span class="booking-timeline-label">Current status</span>
                                            <div class="d-flex flex-wrap align-items-center mb-2 candidate-flex-gap-8">
                                                <strong class="mr-1">Application status:</strong>
                                                <span class="badge badge-<?= $statusColor ?>"><?= esc($candidateStatus) ?></span>
                                            </div>
                                            <p class="booking-timeline-text mb-2"><?= esc($statusSummary) ?></p>
                                            <small class="text-muted d-block"><?= esc($statusNextStep) ?></small>
                                        </div>
                                    </div>
                                </div>

                                <div class="booking-timeline-actions">
                                    <?php if (!empty($booking['calendar_add_link'])): ?>
                                        <a href="<?= esc($booking['calendar_add_link']) ?>" target="_blank" class="btn btn-outline-primary">
                                            <i class="fas fa-calendar-plus mr-1"></i>Add to Calendar
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($canReschedule): ?>
                                        <a href="<?= base_url('candidate/reschedule-slot/' . $booking['application_id']) ?>" class="btn btn-warning">
                                            <i class="fas fa-sync mr-1"></i>Reschedule interview
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="fas fa-ban mr-1"></i>Reschedule closed
                                        </button>
                                    <?php endif; ?>
                                    <span class="booking-timeline-note">Cancellation is not allowed.</span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <?php foreach ($pastBookings as $booking): ?>
                        <?php
                        $statusColors = [
                            'booked' => 'primary',
                            'rescheduled' => 'warning',
                            'completed' => 'success',
                            'no_show' => 'danger',
                            'cancelled' => 'danger'
                        ];
                        $statusLabels = [
                            'booked' => 'Booked',
                            'rescheduled' => 'Rescheduled',
                            'completed' => 'Completed',
                            'no_show' => 'No Show',
                            'cancelled' => 'Cancelled'
                        ];
                        $statusColor = $statusColors[$booking['booking_status']] ?? 'secondary';
                        $candidateStatus = $statusLabels[$booking['booking_status']] ?? ucwords(str_replace('_', ' ', (string) $booking['booking_status']));
                        $statusSummary = match ($booking['booking_status']) {
                            'completed' => 'This interview has finished. Watch this card for the next update from the hiring team.',
                            'no_show' => 'The interview was missed. If needed, check whether the role still allows another slot.',
                            'cancelled' => 'This booking is no longer active.',
                            default => 'This booking is complete. Keep your timeline handy for future references.',
                        };
                        $statusNextStep = match ($booking['booking_status']) {
                            'completed' => 'Stay ready for a recruiter response or next-round invitation.',
                            'no_show' => 'If the role is still open, consider reaching out to the recruiter.',
                            'cancelled' => 'Look for a new interview slot or apply again if the role reopens.',
                            default => 'Continue tracking your interview history here.',
                        };
                        ?>
                        <article class="booking-timeline-item is-past">
                            <div class="booking-timeline-marker">
                                <span>Done</span>
                            </div>
                            <div class="booking-timeline-card">
                                <div class="booking-timeline-head">
                                    <div>
                                        <div class="booking-timeline-title-row">
                                            <h5 class="mb-0">
                                                <i class="fas fa-briefcase mr-2"></i><?= esc($booking['job_title']) ?>
                                            </h5>
                                            <span class="badge badge-<?= $statusColor ?> booking-status-badge">
                                                <?= esc($statusLabels[$booking['booking_status']] ?? ucwords(str_replace('_', ' ', $booking['booking_status']))) ?>
                                            </span>
                                        </div>
                                        <p class="booking-timeline-meta mb-0">
                                            <?= date('l, F j, Y', strtotime($booking['slot_datetime'])) ?> at <?= date('h:i A', strtotime($booking['slot_datetime'])) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="booking-timeline-body">
                                    <div class="booking-timeline-column">
                                        <span class="booking-timeline-label">Outcome</span>
                                        <p class="booking-timeline-text">This interview is complete. Watch this card for your next update.</p>
                                    </div>
                                    <div class="booking-timeline-column">
                                        <span class="booking-timeline-label">Booking details</span>
                                        <ul class="booking-timeline-facts">
                                            <li><strong>Booked on:</strong> <?= date('M j, Y', strtotime($booking['booked_at'])) ?></li>
                                            <li><strong>Reschedules:</strong> <?= $booking['reschedule_count'] ?> / <?= $booking['max_reschedules'] ?></li>
                                        </ul>

                                        <div class="booking-status-card">
                                            <span class="booking-timeline-label">Current status</span>
                                            <div class="d-flex flex-wrap align-items-center mb-2 candidate-flex-gap-8">
                                                <strong class="mr-1">Application status:</strong>
                                                <span class="badge badge-<?= $statusColor ?>"><?= esc($candidateStatus) ?></span>
                                            </div>
                                            <p class="booking-timeline-text mb-2"><?= esc($statusSummary) ?></p>
                                            <small class="text-muted d-block"><?= esc($statusNextStep) ?></small>
                                        </div>
                                    </div>
                                </div>

                                <div class="booking-timeline-actions">
                                    <span class="booking-timeline-note">Interview completed</span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?= view('Layouts/candidate_footer') ?>

