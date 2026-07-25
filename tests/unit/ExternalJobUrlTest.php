<?php

namespace Tests\Unit;

use App\Libraries\ExternalJobUrl;
use CodeIgniter\Test\CIUnitTestCase;

class ExternalJobUrlTest extends CIUnitTestCase
{
    public function testCanonicalizeRemovesTrackingAndNormalizesHostAndPath(): void
    {
        $url = 'http://www.Example.com/jobs/123/?utm_source=mail&jobId=7#apply';
        $this->assertSame('https://example.com/jobs/123?jobId=7', ExternalJobUrl::canonicalize($url));
    }

    public function testEquivalentTrackingUrlsHaveSameHash(): void
    {
        $first = 'https://example.com/job/42?utm_campaign=x&ref=linkedin';
        $second = 'http://www.example.com/job/42/';
        $this->assertSame(ExternalJobUrl::hash($first), ExternalJobUrl::hash($second));
    }

    public function testPostedAgeExpiryUnderstandsRelativeUnits(): void
    {
        $this->assertFalse(ExternalJobUrl::postedAtIsExpired('3 weeks ago'));
        $this->assertTrue(ExternalJobUrl::postedAtIsExpired('2 months ago'));
        $this->assertTrue(ExternalJobUrl::postedAtIsExpired('1 year ago'));
    }

    public function testInvalidAndNonHttpUrlsAreRejected(): void
    {
        $this->assertSame('', ExternalJobUrl::canonicalize('javascript:alert(1)'));
        $this->assertSame('', ExternalJobUrl::canonicalize('not a url'));
    }
}
