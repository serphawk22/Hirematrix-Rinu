<?php

namespace App\Filters;

use App\Libraries\RememberMeService;
use App\Libraries\UsageAnalyticsService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        (new RememberMeService())->attemptAutoLogin();

        if (!session()->get('logged_in')) {
            return redirect()->to(base_url('login?next=' . rawurlencode(current_url())));
        }

        (new UsageAnalyticsService())->captureFirstPageAfterLogin('/' . trim($request->getUri()->getPath(), '/'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
?>
