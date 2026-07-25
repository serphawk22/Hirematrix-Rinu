<?php

namespace Tests\Unit;

use App\Libraries\MncJobIngestor;
use CodeIgniter\Test\CIUnitTestCase;

class MncJobIngestorHealthTest extends CIUnitTestCase
{
    public function testAllFailedRemoteAttemptsReportUnavailable(): void
    {
        $ingestor = new MncJobIngestor();
        $this->record($ingestor, false, 'Tavily', 'timeout');
        $this->record($ingestor, false, 'OpenAI', 'DNS failure');

        $health = $ingestor->getDiscoveryHealth();
        $this->assertSame('external_unavailable', $health['state']);
        $this->assertSame(2, $health['attempts']);
        $this->assertSame(0, $health['responses']);
    }

    public function testSuccessfulSourceWithAnotherFailureReportsDegraded(): void
    {
        $ingestor = new MncJobIngestor();
        $this->record($ingestor, true, 'Career site');
        $this->record($ingestor, false, 'Tavily', 'rate limited');

        $health = $ingestor->getDiscoveryHealth();
        $this->assertSame('degraded', $health['state']);
        $this->assertSame(1, $health['responses']);
    }

    public function testSuccessfulSourcesReportAvailable(): void
    {
        $ingestor = new MncJobIngestor();
        $this->record($ingestor, true, 'Tavily');

        $this->assertSame('available', $ingestor->getDiscoveryHealth()['state']);
    }

    private function record(MncJobIngestor $ingestor, bool $responded, string $service, string $error = ''): void
    {
        $method = new \ReflectionMethod($ingestor, 'recordRemoteResult');
        $method->setAccessible(true);
        $method->invoke($ingestor, $responded, $service, $error);
    }
}
