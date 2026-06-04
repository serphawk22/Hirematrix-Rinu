<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\CompanyModel;
use App\Libraries\TargetCompanyJobService;

class PopulateCompanies extends BaseCommand
{
    protected $group       = 'companies';
    protected $name        = 'companies:populate';
    protected $description = 'Populate companies table using AI scraping from popular list';

    protected $popularCompanies = [
        'Stripe', 'Shopify', 'Airbnb', 'Reddit', 'Figma', 'Notion', 'Discord', 'Dropbox',
        'HubSpot', 'Intercom', 'Linear', 'Vercel', 'Supabase', 'Netlify', 'HashiCorp',
        'Airtable', 'Remote', 'GitLab', 'Google', 'Microsoft', 'Amazon', 'Meta', 'Apple',
        'Netflix', 'Tesla', 'Uber', 'Lyft', 'DoorDash', 'Square', 'PayPal', 'Salesforce',
        'Oracle', 'Adobe', 'ServiceNow', 'Workday', 'Snowflake', 'Databricks', 'MongoDB',
        'Twilio', 'Okta', 'Zoom', 'Slack', 'Asana', 'Monday.com', 'Atlassian', 'Zendesk',
    ];

    public function run(array $params)
    {
        $limit = $params['limit'] ?? 0;
        $service = new TargetCompanyJobService();
        $companyModel = model(CompanyModel::class);

        $companies = $this->popularCompanies;
        if ($limit > 0) {
            $companies = array_slice($companies, 0, $limit);
        }

        CLI::write('Populating ' . count($companies) . ' companies...');

        $success = 0;
        foreach ($companies as $companyName) {
            CLI::write("Processing {$companyName}...", 'yellow');
            $companyInfo = $service->fetchCompanyDetails($companyName);
            
            if (empty($companyInfo['name'])) {
                CLI::write("No info", 'red');
                continue;
            }

            $id = $companyModel->upsertByName($companyName, [
                'name' => $companyInfo['name'],
                'website' => $companyInfo['website'] ?? '',
                'short_description' => $companyInfo['description'] ?? '',
                'career_page' => $companyInfo['career_page'] ?? '',
                'industry' => $companyInfo['industry'] ?? '',
                'size' => $companyInfo['size'] ?? '',
                'hq' => $companyInfo['hq'] ?? '',
            ]);
            
            CLI::write("Saved ID {$id}", 'green');
            $success++;
        }

        CLI::newLine();
        CLI::write("Completed: {$success}/" . count($companies) . " companies populated", 'green');
    }
}

