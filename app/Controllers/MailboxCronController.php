<?php

namespace App\Controllers;

use App\Libraries\RecruiterMailboxService;
use App\Models\RecruiterMailboxConnectionModel;

class MailboxCronController extends BaseController
{
    public function sync()
    {
        $cronSecret = (string) env('cron.secret', '');
        if ($cronSecret !== '' && !hash_equals($cronSecret, (string) $this->request->getGet('secret'))) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'error' => 'Unauthorized',
            ]);
        }

        if ($cronSecret === '' && (string) env('CI_ENVIRONMENT', 'production') === 'production') {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error' => 'Set cron.secret before enabling mailbox cron sync in production.',
            ]);
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('recruiter_mailbox_connections')) {
            return $this->response->setStatusCode(503)->setJSON([
                'success' => false,
                'error' => 'Run mailbox database migrations first.',
            ]);
        }

        $connections = (new RecruiterMailboxConnectionModel())
            ->where('status', 'connected')
            ->findAll();

        $service = new RecruiterMailboxService();
        $imported = 0;
        $failed = 0;
        $mailboxes = [];

        foreach ($connections as $connection) {
            $result = $service->syncConnection($connection);
            $count = (int) ($result['count'] ?? 0);
            $imported += $count;
            if (empty($result['ok'])) {
                $failed++;
            }
            $mailboxes[] = [
                'id' => (int) ($connection['id'] ?? 0),
                'email' => (string) ($connection['email'] ?? ''),
                'provider' => (string) ($connection['provider'] ?? ''),
                'success' => !empty($result['ok']),
                'imported' => $count,
                'error' => !empty($result['ok']) ? null : (string) ($result['error'] ?? 'Sync failed'),
            ];
        }

        return $this->response->setJSON([
            'success' => $failed === 0,
            'connected_mailboxes' => count($connections),
            'imported' => $imported,
            'failed' => $failed,
            'mailboxes' => $mailboxes,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }
}
