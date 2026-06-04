<?php

namespace App\Filters;

use App\Libraries\CandidateOnboardingService;
use App\Libraries\RememberMeService;
use App\Libraries\UsageAnalyticsService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CandidateAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        (new RememberMeService())->attemptAutoLogin();

        if (!session()->get('logged_in')) {
            return redirect()->to(base_url('login?next=' . rawurlencode(current_url())));
        }

        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Candidates only.');
        }

        $path = '/' . trim($request->getUri()->getPath(), '/');
        (new UsageAnalyticsService())->captureFirstPageAfterLogin($path);

        if (str_contains($path, '/candidate/onboarding')) {
            return null;
        }

        $candidateId = (int) session()->get('user_id');
        $onboarding = new CandidateOnboardingService();
        if ($candidateId > 0 && !$onboarding->isComplete($candidateId)) {
            return redirect()->to(base_url('candidate/onboarding/' . $onboarding->getNextStep($candidateId)))
                ->with('error', 'Complete your profile setup to continue.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
