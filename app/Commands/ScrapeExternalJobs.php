<?php

namespace App\Commands;

use App\Libraries\ExternalJobScraperService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ScrapeExternalJobs extends BaseCommand
{
    protected $group = 'Jobs';
    protected $name = 'jobs:scrape-external';
    protected $description = 'Import external jobs from public sources (remotive, remoteok, arbeitnow).';
    protected $usage = 'jobs:scrape-external [--limit 30] [--sources remotive,remoteok,arbeitnow] [--keyword php] [--location india]';
    protected $options = [
        '--limit' => 'How many jobs to process (1-100). Default: 30',
        '--sources' => 'Comma-separated: remotive,remoteok,arbeitnow',
        '--keyword' => 'Optional keyword filter (example: php, laravel, react)',
        '--location' => 'Optional location contains filter (example: india, us, remote)',
    ];

    public function run(array $params)
    {
        $limit = (int) (CLI::getOption('limit') ?? 30);
        $limit = max(1, min(100, $limit));

        $sourcesRaw = trim((string) (CLI::getOption('sources') ?? 'remotive,remoteok,arbeitnow'));
        $sources = array_values(array_filter(array_map('trim', explode(',', $sourcesRaw))));
        $keyword = trim((string) (CLI::getOption('keyword') ?? ''));
        $location = trim((string) (CLI::getOption('location') ?? ''));

        CLI::write('Starting external job import...', 'yellow');
        CLI::write('Limit: ' . $limit);
        CLI::write('Sources: ' . implode(', ', $sources));
        if ($keyword !== '') {
            CLI::write('Keyword: ' . $keyword);
        }
        if ($location !== '') {
            CLI::write('Location: ' . $location);
        }
        CLI::newLine();

        try {
            $result = (new ExternalJobScraperService())->scrapeAndIngest($limit, $sources, $keyword, $location);
        } catch (\Throwable $e) {
            CLI::error('Import failed: ' . $e->getMessage());
            return;
        }

        CLI::write('Done.', 'green');
        CLI::write('Fetched:  ' . (int) ($result['fetched_count'] ?? 0));
        CLI::write('Imported: ' . (int) ($result['imported_count'] ?? 0), 'green');
        CLI::write('Skipped:  ' . (int) ($result['skipped_count'] ?? 0), 'yellow');
        CLI::newLine();

        $sourceStats = (array) ($result['source_stats'] ?? []);
        foreach ($sourceStats as $source => $stats) {
            CLI::write('Source: ' . $source, 'cyan');
            CLI::write('  fetched:  ' . (int) ($stats['fetched'] ?? 0));
            CLI::write('  imported: ' . (int) ($stats['imported'] ?? 0));
            CLI::write('  skipped:  ' . (int) ($stats['skipped'] ?? 0));
            $errors = (array) ($stats['errors'] ?? []);
            foreach ($errors as $error) {
                CLI::write('  error: ' . $error, 'red');
            }
            CLI::newLine();
        }
    }
}
