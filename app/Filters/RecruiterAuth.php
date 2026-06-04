<?php

namespace App\Filters;

use App\Libraries\RememberMeService;
use App\Libraries\UsageAnalyticsService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RecruiterAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        (new RememberMeService())->attemptAutoLogin();

        if (!session()->get('logged_in')) {
            return redirect()->to(base_url('login?next=' . rawurlencode(current_url())));
        }

        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('candidate/dashboard'))->with('error', 'Recruiters only.');
        }

        (new UsageAnalyticsService())->captureFirstPageAfterLogin('/' . trim($request->getUri()->getPath(), '/'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
