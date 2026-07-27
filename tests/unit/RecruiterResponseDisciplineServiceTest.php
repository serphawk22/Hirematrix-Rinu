<?php

namespace Tests\Unit;

use App\Libraries\RecruiterResponseDisciplineService;
use CodeIgniter\Test\CIUnitTestCase;

class RecruiterResponseDisciplineServiceTest extends CIUnitTestCase
{
    public function testBuildsAllFourReminderTypes(): void
    {
        $now = strtotime('2026-07-25 12:00:00');
        $applications = [
            ['id' => 1, 'job_id' => 10, 'status' => 'applied', 'applied_at' => '2026-07-20 10:00:00'],
            ['id' => 2, 'job_id' => 10, 'status' => 'shortlisted', 'applied_at' => '2026-07-10 10:00:00', 'stage_started_at' => '2026-07-22 09:00:00'],
            ['id' => 3, 'job_id' => 10, 'status' => 'interview_slot_booked', 'applied_at' => '2026-07-10 10:00:00', 'booking_id' => 30, 'slot_datetime' => '2026-07-24 09:00:00'],
        ];
        $jobs = [['id' => 10, 'title' => 'Engineer', 'status' => 'open', 'application_deadline' => '2026-07-27']];

        $result = (new RecruiterResponseDisciplineService())->buildReminders($applications, $jobs, [], [], [], $now);

        $this->assertSame(1, $result['counts']['unreviewed']);
        $this->assertSame(1, $result['counts']['shortlist_uncontacted']);
        $this->assertSame(1, $result['counts']['feedback_overdue']);
        $this->assertSame(1, $result['counts']['closing_soon']);
    }

    public function testRecordedActionsContactAndFeedbackSuppressReminders(): void
    {
        $now = strtotime('2026-07-25 12:00:00');
        $applications = [
            ['id' => 1, 'job_id' => 10, 'status' => 'applied', 'applied_at' => '2026-07-20'],
            ['id' => 2, 'job_id' => 10, 'status' => 'shortlisted', 'applied_at' => '2026-07-10', 'stage_started_at' => '2026-07-20'],
            ['id' => 3, 'job_id' => 10, 'status' => 'interviewed', 'applied_at' => '2026-07-10', 'booking_id' => 30, 'slot_datetime' => '2026-07-24'],
        ];

        $result = (new RecruiterResponseDisciplineService())->buildReminders(
            $applications,
            [],
            [2],
            [30],
            [1],
            $now
        );

        $this->assertSame([], $result['items']);
    }

    public function testDashboardSummaryCollapsesVacanciesByReminderType(): void
    {
        $service = new RecruiterResponseDisciplineService();
        $summary = $service->summarizeItems([
            ['type' => 'feedback_overdue', 'title' => 'Interview feedback overdue', 'job_title' => 'PHP Developer', 'count' => 2, 'age_days' => 10],
            ['type' => 'feedback_overdue', 'title' => 'Interview feedback overdue', 'job_title' => 'Data Analyst', 'count' => 1, 'age_days' => 20],
            ['type' => 'shortlist_uncontacted', 'title' => 'Not contacted', 'job_title' => 'DevOps Engineer', 'count' => 1, 'age_days' => 4],
        ]);

        $this->assertCount(2, $summary);
        $this->assertSame(3, $summary[0]['count']);
        $this->assertSame('2 vacancies', $summary[0]['jobs_label']);
        $this->assertSame('Oldest waiting 20 days', $summary[0]['age_label']);
    }
}
