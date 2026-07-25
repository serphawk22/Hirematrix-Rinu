<?php

namespace App\Commands;

use App\Libraries\ExternalJobIntegrityService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MaintainExternalJobs extends BaseCommand
{
    protected $group = 'Jobs';
    protected $name = 'jobs:maintain-external';
    protected $description = 'Deduplicate external jobs and deactivate expired or repeatedly inaccessible links.';
    protected $usage = 'jobs:maintain-external [--limit 100]';
    protected $options = ['--limit' => 'Maximum URLs to check per run (0-1000).'];

    public function run(array $params)
    {
        $limit = max(0, min(1000, (int) (CLI::getOption('limit') ?? 100)));
        $stats = (new ExternalJobIntegrityService())->maintain($limit);

        CLI::write('External job maintenance complete.', 'green');
        CLI::write('Duplicates deactivated: ' . $stats['duplicates']);
        CLI::write('Expired deactivated: ' . $stats['expired']);
        CLI::write('URLs checked: ' . $stats['checked']);
        CLI::write('Unreachable deactivated: ' . $stats['unreachable']);
    }
}
