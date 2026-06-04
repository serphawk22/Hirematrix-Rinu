<?php

namespace App\Controllers;

use App\Models\JobModel;
use App\Models\BlogModel;
use App\Models\CompanyModel;
use App\Models\UserModel;
use App\Models\InterviewBookingModel;

class Home extends BaseController
{
    public function index(): string
    {
        $jobModel = new JobModel();
        $featuredJobs = $jobModel
            ->where('status', 'open')
            ->orderBy('created_at', 'DESC')
            ->findAll(6);

        $companyIds = [];
        $companyNames = [];
        foreach ($featuredJobs as $job) {
            $id = (int) ($job['company_id'] ?? 0);
            if ($id > 0) {
                $companyIds[] = $id;
            }

            $companyName = trim((string) ($job['company'] ?? ''));
            if ($companyName !== '') {
                $companyNames[] = $companyName;
            }
        }

        $companyLogoMap = [];
        $companyNameLogoMap = [];
        if (!empty($companyIds) || !empty($companyNames)) {
            $companyModel = new CompanyModel();
            $builder = $companyModel->select('id, name, logo');

            if (!empty($companyIds) && !empty($companyNames)) {
                $builder->groupStart()
                    ->whereIn('id', array_values(array_unique($companyIds)))
                    ->orWhereIn('name', array_values(array_unique($companyNames)))
                    ->groupEnd();
            } elseif (!empty($companyIds)) {
                $builder->whereIn('id', array_values(array_unique($companyIds)));
            } else {
                $builder->whereIn('name', array_values(array_unique($companyNames)));
            }

            $companies = $builder->findAll();
            foreach ($companies as $company) {
                $logo = trim((string) ($company['logo'] ?? ''));
                if ($logo === '') {
                    continue;
                }

                $companyLogoMap[(int) $company['id']] = $logo;
                $name = strtolower(trim((string) ($company['name'] ?? '')));
                if ($name !== '') {
                    $companyNameLogoMap[$name] = $logo;
                }
            }
        }

        foreach ($featuredJobs as $index => $job) {
            $id = (int) ($job['company_id'] ?? 0);
            if (!empty($companyLogoMap[$id])) {
                $featuredJobs[$index]['company_logo'] = $companyLogoMap[$id];
                continue;
            }

            $jobCompanyName = strtolower(trim((string) ($job['company'] ?? '')));
            $featuredJobs[$index]['company_logo'] = $companyNameLogoMap[$jobCompanyName] ?? '';
        }

        $userModel = new UserModel();
        $interviewBookingModel = new InterviewBookingModel();
        $blogPosts = [];

        $db = \Config\Database::connect();
        if ($db->tableExists('blog_posts')) {
            $blogModel = new BlogModel();
            $blogPosts = $blogModel->getPublishedPosts(3);
        }

        $platformStats = [
            'candidates' => (int) $userModel->where('role', 'candidate')->countAllResults(),
            'jobs_posted' => (int) $jobModel->countAllResults(),
            'interviews_booked' => (int) $interviewBookingModel->countAllResults(),
            'recruiters' => (int) $userModel->where('role', 'recruiter')->countAllResults(),
        ];

        return view('landing', [
            'featuredJobs' => $featuredJobs,
            'platformStats' => $platformStats,
            'blogPosts' => $blogPosts,
        ]);
    }
}
