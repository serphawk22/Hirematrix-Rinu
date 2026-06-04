<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\JobAggregator;

class UpdateCompanyJobs extends BaseCommand
{
    protected $group = 'Jobs';
    protected $name = 'jobs:update-company';
    protected $description = 'Update company jobs from Indeed API';
    protected $usage = 'jobs:update-company [company_name]';
    protected $arguments = [
        'company_name' => 'Optional: Specific company name to update'
    ];

    public function run(array $params = [])
    {
        $jobAggregator = new JobAggregator();
        $companyModel = model('CompanyModel');
        
        try {
            if (!empty($params[0])) {
                // Update specific company
                $companyName = $params[0];
                CLI::write("Fetching jobs for {$companyName}...", 'yellow');
                
                $jobs = $jobAggregator->fetchJobsByCompany($companyName);
                CLI::write("✓ Found " . count($jobs) . " jobs for {$companyName}", 'green');
            } else {
                // Update all companies
                $companies = $companyModel->findAll();
                
                if (empty($companies)) {
                    CLI::write("No companies found in database", 'red');
                    return;
                }
                
                CLI::write("Updating jobs for " . count($companies) . " companies...", 'yellow');
                
                $totalJobs = 0;
                foreach ($companies as $company) {
                    CLI::write("  • {$company['name']}...", 'cyan');
                    $jobs = $jobAggregator->fetchJobsByCompany($company['name']);
                    $totalJobs += count($jobs);
                    CLI::write("    Found " . count($jobs) . " jobs", 'green');
                }
                
                CLI::write("\n✓ Total jobs fetched: {$totalJobs}", 'green');
            }
            
            CLI::write("✓ Company jobs updated successfully", 'green');
        } catch (\Exception $e) {
            CLI::write("✗ Error: " . $e->getMessage(), 'red');
            return 1;
        }
    }
}
