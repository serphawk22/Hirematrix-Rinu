<?php

namespace App\Commands;

use App\Libraries\RecruiterMailboxService;
use App\Models\RecruiterMailboxConnectionModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncRecruiterMailboxes extends BaseCommand
{
    protected $group = 'Mail';
    protected $name = 'mailboxes:sync';
    protected $description = 'Synchronize connected recruiter mailboxes with candidate communication history.';

    public function run(array $params = [])
    {
        if (!\Config\Database::connect()->tableExists('recruiter_mailbox_connections')) {
            CLI::error('Run the database migrations before synchronizing mailboxes.');
            return 1;
        }

        $connections = (new RecruiterMailboxConnectionModel())->where('status', 'connected')->findAll();
        $service = new RecruiterMailboxService();
        $synced = 0;
        $failed = 0;
        foreach ($connections as $connection) {
            $result = $service->syncConnection($connection);
            $synced += (int) ($result['count'] ?? 0);
            if (empty($result['ok'])) {
                $failed++;
                CLI::error(($connection['email'] ?? 'Mailbox') . ': ' . ($result['error'] ?? 'Sync failed'));
            }
        }

        CLI::write("Imported {$synced} new candidate email(s); {$failed} mailbox(es) failed. Previously imported emails remain saved.", $failed ? 'yellow' : 'green');
        return $failed ? 1 : 0;
    }
}
