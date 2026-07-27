<?php

namespace App\Libraries;

class RecruiterResponseDisciplineService
{
    private const UNREVIEWED_DAYS = 3;
    private const SHORTLIST_CONTACT_DAYS = 2;
    private const FEEDBACK_GRACE_HOURS = 4;
    private const CLOSING_SOON_DAYS = 3;

    /** @return array{items:array<int,array<string,mixed>>,counts:array<string,int>} */
    public function getDashboardReminders(int $recruiterId): array
    {
        $data = $this->loadRecruiterData($recruiterId);
        $result = $this->buildReminders(
            $data['applications'],
            $data['jobs'],
            $data['contacted'],
            $data['reviewed'],
            $data['acted'],
            time()
        );
        $result['items'] = $this->summarizeItems($result['items']);
        return $result;
    }

    /** @return array<int,array<int,array<string,mixed>>> */
    public function getApplicationIndicators(int $jobId, int $recruiterId): array
    {
        $data = $this->loadRecruiterData($recruiterId, $jobId);
        $result = $this->buildReminders(
            $data['applications'],
            $data['jobs'],
            $data['contacted'],
            $data['reviewed'],
            $data['acted'],
            time()
        );
        $map = [];
        foreach ($result['items'] as $item) {
            foreach ((array) ($item['application_ids'] ?? []) as $applicationId) {
                $map[(int) $applicationId][] = $item;
            }
        }
        return $map;
    }

    /**
     * Public for deterministic policy tests.
     *
     * @return array{items:array<int,array<string,mixed>>,counts:array<string,int>}
     */
    public function buildReminders(
        array $applications,
        array $jobs,
        array $contactedApplicationIds,
        array $reviewedBookingIds,
        array $actedApplicationIds,
        int $now
    ): array {
        $contacted = array_fill_keys(array_map('intval', $contactedApplicationIds), true);
        $reviewed = array_fill_keys(array_map('intval', $reviewedBookingIds), true);
        $acted = array_fill_keys(array_map('intval', $actedApplicationIds), true);
        $groups = [];
        $counts = ['unreviewed' => 0, 'feedback_overdue' => 0, 'shortlist_uncontacted' => 0, 'closing_soon' => 0];

        foreach ($applications as $application) {
            $applicationId = (int) ($application['id'] ?? 0);
            $jobId = (int) ($application['job_id'] ?? 0);
            $status = strtolower((string) ($application['status'] ?? ''));
            $appliedAt = strtotime((string) ($application['applied_at'] ?? '')) ?: $now;
            $ageDays = max(0, (int) floor(($now - $appliedAt) / 86400));
            $stageStartedAt = strtotime((string) ($application['stage_started_at'] ?? '')) ?: $appliedAt;
            $stageAgeDays = max(0, (int) floor(($now - $stageStartedAt) / 86400));

            if (
                in_array($status, ['applied', 'pending'], true)
                && $ageDays >= self::UNREVIEWED_DAYS
                && !isset($acted[$applicationId])
            ) {
                $this->add($groups, $counts, 'unreviewed', $jobId, $applicationId, $ageDays, [
                    'title' => 'Unreviewed applications',
                    'detail' => 'Candidates have waited at least 3 days for an initial review.',
                    'icon' => 'fas fa-hourglass-half',
                    'tone' => 'danger',
                    'action' => 'Review now',
                ]);
            }

            if (
                $status === 'shortlisted'
                && $stageAgeDays >= self::SHORTLIST_CONTACT_DAYS
                && !isset($contacted[$applicationId])
            ) {
                $this->add($groups, $counts, 'shortlist_uncontacted', $jobId, $applicationId, $stageAgeDays, [
                    'title' => 'Shortlisted candidates not contacted',
                    'detail' => 'Shortlisted candidates have no recorded message, email, or contact outcome.',
                    'icon' => 'fas fa-phone',
                    'tone' => 'warning',
                    'action' => 'Contact candidates',
                ]);
            }

            $bookingId = (int) ($application['booking_id'] ?? 0);
            $slotAt = strtotime((string) ($application['slot_datetime'] ?? '')) ?: 0;
            $bookingStatus = strtolower((string) ($application['booking_status'] ?? 'booked'));
            if (
                $bookingId > 0
                && $slotAt > 0
                && $slotAt <= $now - (self::FEEDBACK_GRACE_HOURS * 3600)
                && in_array($bookingStatus, ['booked', 'confirmed', 'rescheduled', 'completed'], true)
                && !isset($reviewed[$bookingId])
            ) {
                $overdueHours = max(1, (int) floor(($now - $slotAt) / 3600));
                $this->add($groups, $counts, 'feedback_overdue', $jobId, $applicationId, (int) ceil($overdueHours / 24), [
                    'title' => 'Interview feedback overdue',
                    'detail' => 'Completed interviews are waiting for recruiter feedback.',
                    'icon' => 'fas fa-clipboard',
                    'tone' => 'danger',
                    'action' => 'Add feedback',
                ]);
            }
        }

        foreach ($jobs as $job) {
            $jobId = (int) ($job['id'] ?? 0);
            $deadline = strtotime((string) ($job['application_deadline'] ?? '')) ?: 0;
            if ((string) ($job['status'] ?? '') !== 'open' || $deadline < strtotime('today', $now)) {
                continue;
            }
            $days = (int) floor(($deadline - strtotime('today', $now)) / 86400);
            if ($days <= self::CLOSING_SOON_DAYS) {
                $counts['closing_soon']++;
                $groups['closing_soon:' . $jobId] = [
                    'type' => 'closing_soon',
                    'job_id' => $jobId,
                    'job_title' => (string) ($job['title'] ?? 'Vacancy'),
                    'count' => 1,
                    'age_days' => $days,
                    'age_label' => $days === 0 ? 'Closes today' : 'Closes in ' . $days . ' day' . ($days === 1 ? '' : 's'),
                    'title' => 'Vacancy closing soon',
                    'detail' => 'Review remaining applicants before the application deadline.',
                    'icon' => 'fas fa-calendar-times',
                    'tone' => 'warning',
                    'action' => 'Review pipeline',
                    'application_ids' => [],
                ];
            }
        }

        $jobNames = [];
        foreach ($jobs as $job) {
            $jobNames[(int) ($job['id'] ?? 0)] = (string) ($job['title'] ?? 'Vacancy');
        }
        $items = array_values($groups);
        foreach ($items as &$item) {
            $item['job_title'] = $item['job_title'] ?? ($jobNames[(int) $item['job_id']] ?? 'Vacancy');
            $item['url'] = base_url('recruiter/jobs/view/' . (int) $item['job_id']);
        }
        unset($item);
        usort($items, static fn (array $a, array $b): int => (($b['tone'] ?? '') === 'danger' ? 1 : 0) <=> (($a['tone'] ?? '') === 'danger' ? 1 : 0));

        return ['items' => $items, 'counts' => $counts];
    }

    private function add(array &$groups, array &$counts, string $type, int $jobId, int $applicationId, int $ageDays, array $definition): void
    {
        $key = $type . ':' . $jobId;
        $counts[$type]++;
        if (!isset($groups[$key])) {
            $groups[$key] = array_merge($definition, [
                'type' => $type,
                'job_id' => $jobId,
                'count' => 0,
                'age_days' => 0,
                'application_ids' => [],
            ]);
        }
        $groups[$key]['count']++;
        $groups[$key]['age_days'] = max($groups[$key]['age_days'], $ageDays);
        $groups[$key]['age_label'] = $groups[$key]['age_days'] . ' day' . ($groups[$key]['age_days'] === 1 ? '' : 's') . ' waiting';
        $groups[$key]['application_ids'][] = $applicationId;
    }

    /** @return array<int,array<string,mixed>> */
    public function summarizeItems(array $items): array
    {
        $summary = [];
        foreach ($items as $item) {
            $type = (string) ($item['type'] ?? 'other');
            if (!isset($summary[$type])) {
                $summary[$type] = $item;
                $summary[$type]['count'] = 0;
                $summary[$type]['job_titles'] = [];
                $summary[$type]['application_ids'] = [];
                $summary[$type]['age_days'] = $type === 'closing_soon' ? PHP_INT_MAX : 0;
                $summary[$type]['url'] = base_url('recruiter/jobs');
            }
            $summary[$type]['count'] += (int) ($item['count'] ?? 1);
            $jobTitle = trim((string) ($item['job_title'] ?? ''));
            if ($jobTitle !== '') $summary[$type]['job_titles'][] = $jobTitle;
            $summary[$type]['application_ids'] = array_merge(
                $summary[$type]['application_ids'],
                (array) ($item['application_ids'] ?? [])
            );
            $age = (int) ($item['age_days'] ?? 0);
            $summary[$type]['age_days'] = $type === 'closing_soon'
                ? min($summary[$type]['age_days'], $age)
                : max($summary[$type]['age_days'], $age);
        }

        foreach ($summary as $type => &$item) {
            $item['job_titles'] = array_values(array_unique($item['job_titles']));
            $jobCount = count($item['job_titles']);
            $item['jobs_label'] = $jobCount . ' vacanc' . ($jobCount === 1 ? 'y' : 'ies');
            $item['roles_preview'] = implode(', ', array_slice($item['job_titles'], 0, 3))
                . ($jobCount > 3 ? ' +' . ($jobCount - 3) . ' more' : '');
            $days = max(0, (int) $item['age_days']);
            $item['age_label'] = $type === 'closing_soon'
                ? ($days === 0 ? 'Next deadline is today' : 'Next deadline in ' . $days . ' day' . ($days === 1 ? '' : 's'))
                : 'Oldest waiting ' . $days . ' day' . ($days === 1 ? '' : 's');
        }
        unset($item);

        return array_values($summary);
    }

    private function loadRecruiterData(int $recruiterId, ?int $jobId = null): array
    {
        $db = \Config\Database::connect();
        $jobsBuilder = $db->table('jobs');
        if ($jobId !== null) {
            // The caller has already authorized access to this job, including
            // company-team access handled by JobResponsesController.
            $jobsBuilder->where('id', $jobId);
        } else {
            $jobsBuilder->where('recruiter_id', $recruiterId);
        }
        $jobs = $jobsBuilder->get()->getResultArray();
        $jobIds = array_values(array_filter(array_map('intval', array_column($jobs, 'id'))));
        if (!$jobIds) return ['applications' => [], 'jobs' => [], 'contacted' => [], 'reviewed' => [], 'acted' => []];

        $applicationSelect = 'a.id, a.job_id, a.status, a.applied_at, ib.id AS booking_id, ib.slot_datetime, ib.booking_status';
        if ($db->tableExists('stage_history')) {
            $applicationSelect .= ", (SELECT MAX(sh.start_time) FROM stage_history sh WHERE sh.application_id = a.id AND LOWER(sh.stage_name) IN ('shortlisted','interview_scheduled','interviewed')) AS stage_started_at";
        }
        $applications = $db->table('applications a')
            ->select($applicationSelect, false)
            ->join('interview_bookings ib', 'ib.application_id = a.id', 'left')
            ->whereIn('a.job_id', $jobIds)->get()->getResultArray();
        $applicationIds = array_values(array_filter(array_map('intval', array_column($applications, 'id'))));
        $contacted = [];
        $acted = [];
        foreach ([
            ['recruiter_candidate_messages', 'application_id'],
            ['recruiter_email_activities', 'application_id'],
            ['recruiter_communication_outcomes', 'application_id'],
        ] as [$table, $field]) {
            if ($applicationIds && $db->tableExists($table)) {
                $rows = $db->table($table)->select($field)->where('recruiter_id', $recruiterId)->whereIn($field, $applicationIds)->get()->getResultArray();
                $contacted = array_merge($contacted, array_column($rows, $field));
            }
        }
        foreach (['recruiter_candidate_actions', 'recruiter_application_workflows', 'recruiter_communication_outcomes'] as $table) {
            if ($applicationIds && $db->tableExists($table)) {
                $rows = $db->table($table)->select('application_id')->where('recruiter_id', $recruiterId)->whereIn('application_id', $applicationIds)->get()->getResultArray();
                $acted = array_merge($acted, array_column($rows, 'application_id'));
            }
        }
        $reviewed = [];
        if ($db->tableExists('interview_booking_reviews')) {
            $rows = $db->table('interview_booking_reviews')->select('booking_id')->where('recruiter_id', $recruiterId)->where('reviewed_at IS NOT NULL', null, false)->get()->getResultArray();
            $reviewed = array_column($rows, 'booking_id');
        }
        return compact('applications', 'jobs', 'contacted', 'reviewed', 'acted');
    }
}
