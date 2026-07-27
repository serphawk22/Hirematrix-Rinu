<?php

namespace Tests\Unit;

use App\Libraries\CandidateProfileStrengthService;
use CodeIgniter\Test\CIUnitTestCase;

class CandidateProfileStrengthServiceTest extends CIUnitTestCase
{
    public function testEmptyProfileReturnsActionableMissingChecklist(): void
    {
        $result = (new CandidateProfileStrengthService())->evaluate([], null, [], [], []);

        $this->assertSame(0, $result['percentage']);
        $this->assertSame(10, $result['missing_count']);
        $this->assertSame('resume', $result['items'][0]['key']);
        $this->assertContains('outcomes', array_column($result['items'], 'key'));
    }

    public function testCompleteProfileEarnsFullScore(): void
    {
        $user = [
            'name' => 'Candidate',
            'phone' => '1234567890',
            'location' => 'Bangalore',
            'resume_headline' => 'Senior backend engineer building reliable distributed platforms',
            'resume_path' => 'uploads/resume.pdf',
            'preferred_job_titles' => 'Backend Engineer',
            'preferred_locations' => 'Bangalore',
            'preferred_employment_type' => 'Full-time',
            'profile_photo' => 'uploads/photo.jpg',
        ];
        $projects = [
            ['impact_metrics' => 'Reduced API latency by 40%'],
            ['impact_metrics' => 'Served 10,000 monthly users'],
        ];

        $result = (new CandidateProfileStrengthService())->evaluate(
            $user,
            ['skill_name' => 'PHP, MySQL, Redis, Docker, AWS'],
            [['job_title' => 'Engineer']],
            [['degree' => 'B.Tech']],
            $projects
        );

        $this->assertSame(100, $result['percentage']);
        $this->assertSame(0, $result['missing_count']);
    }

    public function testFresherDoesNotRequireWorkExperience(): void
    {
        $result = (new CandidateProfileStrengthService())->evaluate(
            ['is_fresher_candidate' => 1],
            null,
            [],
            [],
            []
        );
        $items = array_column($result['items'], null, 'key');

        $this->assertTrue($items['experience']['completed']);
    }

    public function testTwoMetricsInOneProjectCompleteOutcomeGoal(): void
    {
        $result = (new CandidateProfileStrengthService())->evaluate(
            [],
            null,
            [],
            [],
            [['impact_metrics' => 'Reduced latency by 40% and saved 12 engineering hours per week']]
        );
        $items = array_column($result['items'], null, 'key');

        $this->assertTrue($items['outcomes']['completed']);
    }
}
