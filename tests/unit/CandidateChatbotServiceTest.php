<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\CandidateChatbotService;

/**
 * @internal
 */
final class CandidateChatbotServiceTest extends CIUnitTestCase
{
    public function testAnswerFallsBackGracefullyWithoutApiKey(): void
    {
        putenv('OPENAI_API_KEY');
        $_ENV['OPENAI_API_KEY'] = '';
        putenv('OPENAI_API_KEY=');

        $service = new CandidateChatbotService();
        $result = $service->answer(1, 'What jobs are available for me?');

        $this->assertSame(true, $result['answer'] !== '');
        $this->assertSame([], $result['data_summary']);
    }
}
