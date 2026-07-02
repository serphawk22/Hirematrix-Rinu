<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CandidateDashboardControllerTest extends CIUnitTestCase
{
    public function testDashboardApplicationEnrichmentCanSkipInterviewPrepGeneration(): void
    {
        $controller = new class extends \App\Controllers\CandidateDashboardController {
            public function exposeEnrichApplicationData(array $application, bool $includeInterviewPrep = false): array
            {
                return $this->enrichApplicationData($application, $includeInterviewPrep);
            }
        };

        $application = [
            'id' => 42,
            'status' => 'applied',
            'job_id' => 77,
            'job_title' => 'Senior PHP Engineer',
            'company' => 'Example Co',
            'required_skills' => 'php, mysql',
            'experience_level' => 'Mid',
            'ai_interview_policy' => 'REQUIRED_HARD',
        ];

        $result = $controller->exposeEnrichApplicationData($application, false);

        $this->assertSame([], $result['interview_prep'] ?? null);
        $this->assertSame('Start your AI interview to move forward.', $result['next_action'] ?? '');
        $this->assertSame('', $result['interview_review_label'] ?? '');
    }
}
