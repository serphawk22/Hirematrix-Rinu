<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');
$routes->post('recruiter/get-ai-report', 'Recruiter::getAiReport');
// Route to serve the standalone portal trailer page
$routes->get('portal-trailer', function() {
    $path = ROOTPATH . 'standalone/portal-trailer.html';
    if (!is_file($path)) {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }
    return service('response')->setBody(file_get_contents($path))->setContentType('text/html');
});
// app/Config/Routes.php  ← route goes HERE, not in the controller file
$routes->post('job/mark-visited/(:num)', 'JobController::markVisited/$1');
// Login
$routes->get('feedback', 'CandidateFeedbackController::index');
$routes->post('feedback/save', 'CandidateFeedbackController::save');
$routes->get('/localcompany', 'Companies::index');
$routes->get('/fetch-companies', 'Companies::fetchCompanies');
$routes->get('/suggest', 'Companies::suggest');
$routes->get('/resolve-current-location', 'Companies::resolveLocation');

$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::authenticate');
$routes->get('logout', 'Auth::logout');
$routes->get('about', 'LegalPages::about');
$routes->get('contact', 'LegalPages::contact');
$routes->post('contact', 'LegalPages::submitContact');
$routes->get('privacy-policy', 'LegalPages::privacyPolicy');
$routes->get('terms-of-service', 'LegalPages::termsOfService');
$routes->get('admin/login', 'AdminAnalytics::login');
$routes->post('admin/login', 'AdminAnalytics::authenticate');
$routes->get('admin/logout', 'AdminAnalytics::logout', ['filter' => 'admin']);
$routes->get('admin/dashboard', 'AdminAnalytics::dashboard', ['filter' => 'admin']);
$routes->get('admin/users', 'AdminUserController::index', ['filter' => 'admin']);
$routes->get('admin/users/suggestions', 'AdminUserController::suggestions', ['filter' => 'admin']);
$routes->post('admin/users/recruiter-verification/(:num)', 'AdminUserController::updateRecruiterVerification/$1', ['filter' => 'admin']);
$routes->get('admin/feedback', 'AdminFeedbackController::index', ['filter' => 'admin']);
$routes->get('admin/feedback/suggestions', 'AdminFeedbackController::suggestions', ['filter' => 'admin']);
$routes->get('admin/companies', 'AdminCompanyController::index', ['filter' => 'admin']);
$routes->get('admin/companies/suggestions', 'AdminCompanyController::suggestions', ['filter' => 'admin']);
$routes->get('admin/company/(:num)', 'AdminCompanyController::getCompany/$1', ['filter' => 'admin']); // for popup
$routes->post('admin/company/delete/(:num)', 'AdminCompanyController::delete/$1', ['filter' => 'admin']);
$routes->get('admin/jobs', 'AdminJobController::index', ['filter' => 'admin']);
$routes->get('admin/jobs/suggestions', 'AdminJobController::suggestions', ['filter' => 'admin']);
$routes->post('admin/jobs/import-manual', 'JobFeedScheduler::importManual', ['filter' => 'admin']);
$routes->get('admin/jobs/import-stats', 'JobFeedScheduler::getImportStats', ['filter' => 'admin']);
$routes->get('admin/job/(:num)', 'AdminJobController::getJob/$1', ['filter' => 'admin']);
$routes->get('admin/blogs', 'AdminBlogController::index', ['filter' => 'admin']);
$routes->get('admin/subscriptions', 'AdminAnalytics::subscriptions', ['filter' => 'admin']);
$routes->get('admin/subscription/(:num)', 'AdminAnalytics::viewSubscription/$1', ['filter' => 'admin']);
$routes->get('admin/blogs/create', 'AdminBlogController::create', ['filter' => 'admin']); // Use blog_form for create
$routes->post('admin/blogs/store', 'AdminBlogController::store', ['filter' => 'admin']);
$routes->get('admin/blogs/edit/(:num)', 'AdminBlogController::edit/$1', ['filter' => 'admin']);
$routes->post('admin/blogs/update/(:num)', 'AdminBlogController::update/$1', ['filter' => 'admin']);
$routes->post('admin/blogs/delete/(:num)', 'AdminBlogController::delete/$1', ['filter' => 'admin']);

$routes->get('admin/company-ats-mappings', 'AdminCompanyAtsMappings::index', ['filter' => 'admin']);
$routes->post('admin/company-ats-mappings/save', 'AdminCompanyAtsMappings::save', ['filter' => 'admin']);
$routes->post('admin/company-ats-mappings/import', 'AdminCompanyAtsMappings::import', ['filter' => 'admin']);
$routes->get('admin/company-ats-mappings/template', 'AdminCompanyAtsMappings::template', ['filter' => 'admin']);
$routes->get('admin/company-ats-mappings/delete/(:num)', 'AdminCompanyAtsMappings::delete/$1', ['filter' => 'admin']);
$routes->get('forgot-password', 'Auth::forgotPassword');
$routes->post('forgot-password', 'Auth::sendPasswordResetLink');
$routes->get('reset-password/(:any)', 'Auth::resetPassword/$1');
$routes->post('reset-password/(:any)', 'Auth::updatePassword/$1');
$routes->get('account/change-password', 'Auth::changePassword', ['filter' => 'auth']);
$routes->post('account/change-password', 'Auth::saveChangedPassword', ['filter' => 'auth']);

// Candidate registration
$routes->get('register', 'Auth::registerCandidate');
$routes->post('register', 'Auth::saveCandidate');
$routes->get('auth/google', 'Auth::googleCandidateStart');
$routes->post('auth/parse-resume', 'Auth::parseResumeForOnboarding');
$routes->get('auth/google/callback', 'Auth::googleCandidateCallback');

// API Routes for Flutter App
$routes->group('api', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->post('login', 'ApiAuthController::login');
    $routes->post('google-login', 'ApiAuthController::googleLogin');
    $routes->post('register', 'ApiAuthController::register');
    $routes->post('recruiter/register', 'ApiAuthController::registerRecruiter');
    $routes->post('recruiter/verify-email', 'ApiAuthController::verifyRecruiterEmail');
    $routes->post('recruiter/resend-verification', 'ApiAuthController::resendRecruiterVerification');
    $routes->post('onboarding/(:segment)', 'ApiOnboardingController::saveStep/$1');
    $routes->get('profile/(:num)', 'ApiProfileController::getProfile/$1');
    $routes->post('profile/update_personal', 'ApiProfileController::updatePersonal');
    $routes->post('profile/update_career', 'ApiProfileController::updateCareer');
    $routes->post('profile/update_preferences', 'ApiProfileController::updatePreferences');
    $routes->post('profile/update_settings', 'ApiProfileController::updateSettings');
    $routes->post('change-password', 'ApiAuthController::changePassword');
    $routes->post('forgot-password', 'ApiAuthController::forgotPassword');
    $routes->post('reset-password', 'ApiAuthController::resetPassword');
    $routes->post('profile/add_skill', 'ApiProfileController::addSkill');
    $routes->post('profile/remove_skill', 'ApiProfileController::removeSkill');
    $routes->post('profile/analyze_github', 'ApiProfileController::analyzeGithub');
    $routes->post('profile/update_interests', 'ApiProfileController::updateInterests');
    $routes->post('profile/save_experience', 'ApiProfileController::saveExperience');
    $routes->post('profile/delete_experience/(:num)', 'ApiProfileController::deleteExperience/$1');
    $routes->post('profile/save_project', 'ApiProfileController::saveProject');
    $routes->post('profile/delete_project/(:num)', 'ApiProfileController::deleteProject/$1');
    $routes->post('profile/save_education', 'ApiProfileController::saveEducation');
    $routes->post('profile/delete_education/(:num)', 'ApiProfileController::deleteEducation/$1');
    $routes->post('profile/save_certification', 'ApiProfileController::saveCertification');
    $routes->post('profile/delete_certification/(:num)', 'ApiProfileController::deleteCertification/$1');
    $routes->post('profile/upload_resume', 'ApiProfileController::uploadResume');
    $routes->post('profile/upload_video', 'ApiProfileController::uploadVideo');
    $routes->post('profile/upload_photo', 'ApiProfileController::uploadPhoto');
    $routes->post('profile/delete_photo', 'ApiProfileController::deletePhoto');
    $routes->get('dashboard/(:num)', 'ApiDashboardController::getDashboard/$1');
    $routes->get('job-search-strategy/(:num)', 'ApiDashboardController::getJobSearchStrategy/$1');
    $routes->get('jobs/featured', 'ApiJobsController::getFeaturedJobs');
    $routes->get('local-companies/init', 'Companies::initLocalCompanies');
    $routes->get('jobs/detail/(:num)', 'ApiJobsController::getJobDetails/$1');
    $routes->get('jobs/(:num)', 'ApiJobsController::getJobs/$1');
    $routes->get('jobs/saved/(:num)', 'ApiJobsController::getSavedJobs/$1');
    $routes->post('jobs/save', 'ApiJobsController::saveJob');
    $routes->post('jobs/unsave', 'ApiJobsController::unsaveJob');
    $routes->post('jobs/apply/(:num)', 'ApiJobsController::applyJob/$1');
    $routes->get('jobs/generate-ai-cover-letter', 'ApiJobsController::generateAiCoverLetter');
    $routes->get('jobs/analyze-ats-match', 'ApiJobsController::analyzeAtsMatch');
    $routes->get('jobs/invitation', 'ApiJobsController::getJobInvitation');
    $routes->get('company/(:num)', 'ApiJobsController::getCompanyProfile/$1');
    $routes->post('company/(:num)/review', 'ApiJobsController::submitCompanyReview/$1');
    $routes->get('plans/(:num)', 'ApiDashboardController::getPlans/$1');
    $routes->post('plans/subscribe', 'ApiDashboardController::subscribe');
    $routes->post('payment/create-order', 'ApiPaymentController::createOrder');
    $routes->post('payment/verify', 'ApiPaymentController::verify');
    $routes->get('applications/(:num)', 'ApiApplicationsController::getApplications/$1');
    $routes->post('applications/withdraw/(:num)', 'ApiApplicationsController::withdraw/$1');
    $routes->get('applications/(:num)/slots', 'ApiApplicationsController::getAvailableSlots/$1');
    $routes->post('applications/book-slot', 'ApiApplicationsController::processBooking');
    $routes->get('applications/bookings/(:num)', 'ApiApplicationsController::getMyBookings/$1');
    $routes->get('applications/(:num)/reschedule-info', 'ApiApplicationsController::getRescheduleInfo/$1');
    $routes->post('applications/reschedule', 'ApiApplicationsController::processReschedule');
    
    // Notification API Routes
    $routes->get('notifications/(:num)', 'ApiNotificationController::getNotifications/$1');
    $routes->post('notifications/mark-read/(:num)', 'ApiNotificationController::markAsRead/$1');
    $routes->post('notifications/mark-all-read/(:num)', 'ApiNotificationController::markAllAsRead/$1');
    $routes->post('notifications/delete/(:num)', 'ApiNotificationController::deleteNotification/$1');

    // Message API Routes
    $routes->get('messages/thread', 'ApiMessagesController::getThread');
    $routes->post('messages/reply', 'ApiMessagesController::sendReply');
    
    // Mobile Recruiter API Routes
    $routes->group('mobile', function($routes) {
        $routes->get('test', function() {
            try {
                $db = \Config\Database::connect();
                $db->connect();
                return service('response')->setJSON([
                    'success' => true,
                    'message' => 'Connection successful',
                    'database' => $db->getDatabase()
                ]);
            } catch (\Exception $e) {
                return service('response')->setJSON([
                    'success' => true,
                    'message' => 'Server reached, but DB error: ' . $e->getMessage()
                ]);
            }
        });
        
        $routes->post('login', 'ApiAuthController::login');
        $routes->post('validate_session', 'ApiAuthController::validateSession');
        $routes->post('forgot_password', 'ApiAuthController::forgotPassword');
        $routes->post('reset_password', 'ApiAuthController::resetPassword');
        $routes->post('resend_verification', 'ApiAuthController::resendVerification');
        $routes->post('signup', 'ApiAuthController::register');
        $routes->post('change-password', 'ApiAuthController::changePassword');
        $routes->post('recruiter/change-password', 'API_RecruiterController::changePassword');
        $routes->post('chatbot/ask', 'API_RecruiterController::askChatbot');
        $routes->get('chatbot/suggestions', 'API_RecruiterController::getChatbotSuggestions');
        $routes->get('dashboard', 'API_RecruiterController::getDashboard');
        $routes->get('export/excel', 'API_RecruiterController::exportExcel');
        $routes->get('dashboard/leaderboard', 'API_RecruiterController::getLeaderboard');
        $routes->get('jobs', 'API_RecruiterController::getJobs');
        $routes->post('jobs/add', 'API_RecruiterController::addJob');
        $routes->post('jobs/update', 'API_RecruiterController::updateJob');
        $routes->post('jobs/update-status', 'API_RecruiterController::updateJobStatus');
        $routes->get('applications', 'API_RecruiterController::getApplications');
        $routes->get('candidates', 'API_RecruiterController::getCandidateDatabase');
        $routes->post('candidates/invite', 'API_RecruiterController::inviteCandidate');
        $routes->post('candidates/bulk_invite', 'API_RecruiterController::bulkInviteCandidate');
        $routes->get('candidates/(:num)', 'API_RecruiterController::getCandidateProfile/$1');
        $routes->post('candidates/(:num)/action', 'API_RecruiterController::logCandidateAction/$1');
        $routes->post('candidates/(:num)/message', 'API_RecruiterController::sendCandidateMessage/$1');
        $routes->post('candidates/(:num)/notes', 'API_RecruiterController::saveCandidateNotes/$1');
        $routes->get('candidates/(:num)/resume', 'API_RecruiterController::downloadCandidateResume/$1');
        $routes->get('interviews', 'API_RecruiterController::getInterviews');
        $routes->get('notifications', 'API_RecruiterController::getNotifications');
        $routes->post('notifications/mark_read', 'API_RecruiterController::markNotificationRead');
        $routes->post('notifications/delete', 'API_RecruiterController::deleteNotification');
        $routes->get('company', 'API_RecruiterController::getCompany');
        $routes->post('company/update', 'API_RecruiterController::updateCompanyProfile');
        $routes->post('company/upload_photo', 'API_RecruiterController::uploadCompanyImage');
        $routes->post('company/delete_photo', 'API_RecruiterController::deleteCompanyImage');
        $routes->post('support/chat', 'API_SupportController::chat');
        $routes->get('profile', 'API_RecruiterController::getProfile');
        $routes->post('profile/update', 'API_RecruiterController::updateProfile');
        $routes->get('settings', 'API_RecruiterController::getSettings');
        $routes->post('settings/update', 'API_RecruiterController::updateSettings');
        $routes->get('activity', 'API_RecruiterController::getActivity');
        $routes->get('team', 'API_RecruiterController::getTeam');
        $routes->post('team/invite', 'API_RecruiterController::inviteMember');
        $routes->post('applications/update_status', 'API_RecruiterController::updateApplicationStatus');
        $routes->post('applications/bulk_update_status', 'API_RecruiterController::bulkUpdateApplicationStatus');
        $routes->post('applications/bulk_email', 'API_RecruiterController::bulkSendEmail');
        $routes->post('applications/bulk_message', 'API_RecruiterController::bulkSendMessage');
        $routes->post('update_fcm_token', 'API_RecruiterController::updateFcmToken');

        // Interview Management
        $routes->get('interview_slots', 'API_RecruiterController::getInterviewSlots');
        $routes->post('interview_slots/add', 'API_RecruiterController::addInterviewSlot');
        $routes->post('interview_slots/update', 'API_RecruiterController::updateInterviewSlot');
        $routes->post('interview_slots/delete', 'API_RecruiterController::deleteInterviewSlot');
        $routes->get('interview_bookings', 'API_RecruiterController::getInterviewBookings');
        $routes->post('interviews/reschedule', 'API_RecruiterController::rescheduleInterviewBooking');
        $routes->get('interviews/reschedule/data', 'API_RecruiterController::getRescheduleData');
        $routes->post('interviews/reschedule/process', 'API_RecruiterController::processRescheduleData');
        $routes->post('interviews/review', 'API_RecruiterController::submitInterviewReview');
    });
    // Career Transition AI API Routes
    $routes->get('career-transition/(:num)', 'ApiCareerTransitionController::getTransition/$1');
    $routes->post('career-transition/create', 'ApiCareerTransitionController::create');
    $routes->post('career-transition/complete-task/(:num)', 'ApiCareerTransitionController::completeTask/$1');
    $routes->get('career-transition/modules/(:num)', 'ApiCareerTransitionController::getModules/$1');
    $routes->get('career-transition/module-lessons/(:num)', 'ApiCareerTransitionController::getLessons/$1');
    $routes->post('career-transition/reset', 'ApiCareerTransitionController::reset');
    $routes->get('career-transition/history/(:num)', 'ApiCareerTransitionController::history/$1');
    $routes->post('career-transition/reactivate', 'ApiCareerTransitionController::reactivate');

    // Resume Studio API Routes
    $routes->get('resume-studio/(:num)', 'ApiResumeStudioController::getStudioData/$1');
    $routes->post('resume-studio/generate', 'ApiResumeStudioController::generate');
    $routes->post('resume-studio/sync-transition', 'ApiResumeStudioController::syncTransition');
    $routes->post('resume-studio/set-primary', 'ApiResumeStudioController::setPrimary');
    $routes->post('resume-studio/delete', 'ApiResumeStudioController::delete');
    $routes->get('resume-studio/download-pdf/(:num)/(:num)', 'ApiResumeStudioController::downloadResumeVersion/$1/$2');
    $routes->get('resume-studio/preview-html/(:num)/(:num)', 'ApiResumeStudioController::previewResumeVersion/$1/$2');

    // Premium Mentor API Routes
    $routes->get('premium-mentor/(:num)', 'ApiPremiumMentorController::getMentorData/$1');
    $routes->post('premium-mentor/chat', 'ApiPremiumMentorController::chat');
    $routes->post('premium-mentor/create-plan', 'ApiPremiumMentorController::createPlan');
});

// recruiter registration (restricted)
$routes->get('recruiter/register', 'Auth::registerAdmin');
$routes->post('recruiter/register', 'Auth::saveAdmin');
$routes->get('recruiter/verification', 'Auth::recruiterVerification');
$routes->post('recruiter/verify-email-code', 'Auth::submitRecruiterEmailCode');
$routes->post('recruiter/resend-verification-email', 'Auth::resendRecruiterVerificationEmail');
$routes->post('recruiter/send-phone-otp', 'Auth::sendRecruiterPhoneOtp');
$routes->post('recruiter/verify-phone-otp', 'Auth::submitRecruiterPhoneOtp');
$routes->get('company/(:num)', 'CompanyProfile::show/$1', ['filter' => 'auth']);
$routes->post('company/(:num)/review', 'CompanyProfile::submitReview/$1', ['filter' => 'candidate']);

// $routes->get('dashboard', 'Auth::dashboard');
// Candidate Dashboard Routes
$routes->group('candidate', ['namespace' => 'App\Controllers', 'filter' => 'candidate'], function($routes) {
    $routes->get('onboarding', 'CandidateOnboarding::index');
    $routes->get('onboarding/(:segment)', 'CandidateOnboarding::step/$1');
    $routes->post('onboarding/(:segment)', 'CandidateOnboarding::save/$1');
    $routes->get('dashboard', 'CandidateDashboardController::index');
    $routes->get('/', 'CandidateDashboardController::index'); // Default route
    $routes->get('applications', 'CandidateDashboardController::applications');
    $routes->get('generate-ai-cover-letter', 'CandidateDashboardController::generateAiCoverLetter');
    $routes->get('analyze-ats-match', 'CandidateDashboardController::analyzeAtsMatch');
    $routes->get('job-search-strategy', 'CandidateDashboardController::jobSearchStrategy');
    $routes->get('applications/(:num)/mock-interview', 'CandidateDashboardController::mockInterview/$1');
    $routes->get('saved-jobs', 'SavedJobs::index');
    $routes->get('company-job-discovery', 'CandidateDashboardController::companyJobDiscovery'); // New route for combined discovery page
    $routes->get('job-alerts', 'JobAlerts::index');
    $routes->post('job-alerts/settings', 'JobAlerts::updateSettings');
    $routes->post('job-alerts/create', 'JobAlerts::create');
    $routes->get('job-alerts/toggle/(:num)', 'JobAlerts::toggle/$1');
    $routes->get('job-alerts/delete/(:num)', 'JobAlerts::delete/$1');
    $routes->get('messages/(:num)', 'CandidateMessages::thread/$1');
    $routes->post('messages/(:num)/reply', 'CandidateMessages::reply/$1');
    $routes->get('blog/(:num)', 'CandidateDashboardController::blogDetail/$1');
});

// Career Transition AI Routes
$routes->group('career-transition', ['filter' => 'candidate'], function($routes) {
    $routes->get('/', 'CareerTransition::index');
    $routes->post('create', 'CareerTransition::create');
    $routes->post('complete/(:num)', 'CareerTransition::completeTask/$1');
    $routes->get('course', 'CareerTransition::course');
    $routes->get('module/(:num)', 'CareerTransition::module/$1');
    $routes->post('dismiss-suggestion', 'CareerTransition::dismissSuggestion');
    $routes->get('reset', 'CareerTransition::reset');
});
// NEW: PDF Download Route
$routes->get('career-transition/download-pdf', 'CareerTransitionPDF_TCPDF::downloadCoursePDF', ['filter' => 'candidate']);
$routes->get('career-transition/download-pdf/(:num)', 'CareerTransitionPDF_TCPDF::downloadCoursePDF/$1');
// Career Transition History Routes
$routes->get('career-transition/history', 'CareerTransition::history', ['filter' => 'candidate']);
$routes->get('career-transition/reactivate/(:num)', 'CareerTransition::reactivate/$1', ['filter' => 'candidate']);
// Dashboard Routes (Admin)
// AI Chatbot routes — no auth filter needed because the controller handles auth itself
// and returns proper JSON errors instead of HTML redirects that break fetch()
$routes->post('recruiter/chatbot/ask', 'RecruiterChatbotController::ask');
$routes->get('recruiter/chatbot/suggestions', 'RecruiterChatbotController::suggestions');

$routes->group('recruiter', ['namespace' => 'App\Controllers', 'filter' => 'recruiter'], function($routes) {
    
    // Main Dashboard
    $routes->get('dashboard', 'DashboardController::index');
    $routes->get('settings', 'Recruiter::settings');
    $routes->get('mailbox/connect/(:segment)', 'RecruiterMailbox::connect/$1');
    $routes->get('mailbox/callback/(:segment)', 'RecruiterMailbox::callback/$1');
    $routes->post('mailbox/connect-custom', 'RecruiterMailbox::connectCustom');
    $routes->post('mailbox/disconnect', 'RecruiterMailbox::disconnect');
    $routes->post('mailbox/sync', 'RecruiterMailbox::sync');
    $routes->get('mailbox/poll', 'RecruiterMailbox::poll');
    
    // Leaderboard
    $routes->get('dashboard/leaderboard', 'DashboardController::leaderboard');
    $routes->get('jobs/(:num)/leaderboard', 'DashboardController::leaderboard/$1');
    
    // Excel Exports
    $routes->get('dashboard/export-excel', 'DashboardController::exportExcel');
    $routes->post('settings/workflow', 'Recruiter::updateWorkflowSettings');
    
    // Applications by Job (legacy index kept as redirect)
    $routes->get('applications', 'RecruiterApplications::index');
    // Legacy application URLs now resolve to the current job pipeline.
    $routes->get('applications/job/(:num)', 'JobResponsesController::viewJob/$1');
    $routes->get('jobs/(:num)/applications', 'JobResponsesController::viewJob/$1');
    $routes->post('jobs/(:num)/applications/bulk', 'RecruiterApplications::bulkAction/$1');
    $routes->post('applications/shortlist/(:num)', 'RecruiterApplications::shortlist/$1');
    $routes->post('applications/reject/(:num)', 'RecruiterApplications::reject/$1');
    
    // Job Management
    $routes->get('jobs', 'JobResponsesController::index');
    $routes->get('jobs/view/(:num)', 'JobResponsesController::viewJob/$1');
    $routes->get('jobs/preview/(:num)', 'JobResponsesController::previewJob/$1');
    $routes->post('applications/update-status/(:num)', 'JobResponsesController::updateApplicationStatus/$1');
    $routes->post('applications/schedule-interview/(:num)', 'JobResponsesController::scheduleInterview/$1');
    $routes->post('jobs/(:num)/send-bulk-email', 'JobResponsesController::sendBulkEmail/$1');
    
    $routes->get('candidates', 'RecruiterCandidates::index');
    $routes->post('candidates/send-bulk-email', 'RecruiterCandidates::sendBulkEmail');
    $routes->get('jobs/edit/(:num)', 'RecruiterJobs::edit/$1');
    $routes->post('jobs/update/(:num)', 'RecruiterJobs::update/$1');
    $routes->get('jobs/close/(:num)', 'RecruiterJobs::close/$1');
    $routes->get('jobs/reopen/(:num)', 'RecruiterJobs::reopen/$1');
    $routes->get('company-profile', 'CompanyProfile::edit');
    $routes->post('company-profile', 'CompanyProfile::update');
});

$routes->get('jobs', 'Jobs::index', ['filter' => 'candidate']);
$routes->get('job/(:num)', 'Jobs::jobDetail/$1', ['filter' => 'candidate']);
$routes->post('job/apply/(:num)', 'Applications::apply/$1', ['filter' => 'candidate']);
$routes->post('candidate/applications/withdraw/(:num)', 'Applications::withdraw/$1', ['filter' => 'candidate']);
$routes->get('job/save/(:num)', 'SavedJobs::save/$1', ['filter' => 'candidate']);
$routes->get('job/unsave/(:num)', 'SavedJobs::unsave/$1', ['filter' => 'candidate']);

$routes->get('recruiter/post_job', 'Recruiter::postJob', ['filter' => 'recruiter']);
$routes->post('recruiter/post_job', 'Recruiter::saveJob', ['filter' => 'recruiter']);

$routes->get('candidate/profile', 'Candidate::profile', ['filter' => 'candidate']);
$routes->get('candidate/settings', 'Candidate::settings', ['filter' => 'candidate']);
$routes->get('candidate/get-settings-ajax', 'Candidate::getSettingsAjax', ['filter' => 'candidate']);
$routes->post('candidate/update-notification-settings', 'Candidate::updateNotificationSettings', ['filter' => 'candidate']);
$routes->get('candidate/resume-studio', 'Candidate::resumeStudio', ['filter' => 'candidate']);
$routes->post('candidate/resume_upload', 'Candidate::resumeUpload', ['filter' => 'candidate']);
$routes->post('candidate/resume/generate', 'Candidate::generateAiResume', ['filter' => 'candidate']);
$routes->post('candidate/resume/sync-transition', 'Candidate::syncResumeFromTransition', ['filter' => 'candidate']);
$routes->post('candidate/resume-version/(:num)/primary', 'Candidate::setPrimaryResumeVersion/$1', ['filter' => 'candidate']);
$routes->post('candidate/resume-version/(:num)/delete', 'Candidate::deleteResumeVersion/$1', ['filter' => 'candidate']);
$routes->get('candidate/resume-version/(:num)/download', 'Candidate::downloadResumeVersion/$1', ['filter' => 'candidate']);
$routes->get('candidate/resume-version/(:num)/preview', 'Candidate::previewResumeVersion/$1', ['filter' => 'candidate']);
$routes->post('candidate/analyze_github', 'Candidate::analyzeGithubSkills', ['filter' => 'candidate']);
$routes->get('candidate/download-resume', 'Candidate::downloadResume', ['filter' => 'candidate']);
$routes->get('candidate/preview-resume', 'Candidate::previewResume', ['filter' => 'candidate']);
$routes->get('candidate/serve-resume', 'Candidate::serveResume', ['filter' => 'candidate']);
$routes->post('candidate/add-skill', 'Candidate::addSkill', ['filter' => 'candidate']);
$routes->post('candidate/update_personal', 'Candidate::updatePersonal', ['filter' => 'candidate']);
$routes->post('candidate/update-career-details', 'Candidate::updateCareerDetails', ['filter' => 'candidate']);
$routes->post('candidate/update-intro-video', 'Candidate::updateIntroVideo', ['filter' => 'candidate']);
$routes->post('candidate/update-preferences', 'Candidate::updatePreferences', ['filter' => 'candidate']);
$routes->post('candidate/update-settings', 'Candidate::updateSettings', ['filter' => 'candidate']);
$routes->post('candidate/upload-photo', 'Candidate::uploadPhoto', ['filter' => 'candidate']);
$routes->post('candidate/remove-photo', 'Candidate::removePhoto', ['filter' => 'candidate']);
$routes->post('candidate/upload-intro-video', 'Candidate::uploadIntroVideo', ['filter' => 'candidate']);
$routes->post('candidate/remove-intro-video', 'Candidate::removeIntroVideo', ['filter' => 'candidate']);
$routes->post('candidate/add-work-experience', 'Candidate::addWorkExperience', ['filter' => 'candidate']);
$routes->get('candidate/delete-work-experience/(:num)', 'Candidate::deleteWorkExperience/$1', ['filter' => 'candidate']);
$routes->post('candidate/add-education', 'Candidate::addEducation', ['filter' => 'candidate']);
$routes->get('candidate/delete-education/(:num)', 'Candidate::deleteEducation/$1', ['filter' => 'candidate']);
$routes->post('candidate/add-certification', 'Candidate::addCertification', ['filter' => 'candidate']);
$routes->get('candidate/delete-certification/(:num)', 'Candidate::deleteCertification/$1', ['filter' => 'candidate']);
$routes->post('candidate/add-project', 'Candidate::addProject', ['filter' => 'candidate']);
$routes->get('candidate/delete-project/(:num)', 'Candidate::deleteProject/$1', ['filter' => 'candidate']);
$routes->post('candidate/add-interest', 'Candidate::addInterest', ['filter' => 'candidate']);
$routes->get('candidate/delete-interest/(:any)', 'Candidate::deleteInterest/$1', ['filter' => 'candidate']);

$routes->get('recruiter/candidate/(:num)', 'RecruiterCandidates::viewProfile/$1', ['filter' => 'recruiter']);
$routes->get('recruiter/candidate/(:num)/view-contact', 'RecruiterCandidates::viewContact/$1', ['filter' => 'recruiter']);
$routes->get('recruiter/candidate/(:num)/download-resume', 'RecruiterCandidates::downloadResume/$1', ['filter' => 'recruiter']);
$routes->get('recruiter/candidate/(:num)/preview-resume', 'RecruiterCandidates::previewResume/$1', ['filter' => 'recruiter']);
$routes->post('recruiter/candidate/(:num)/send-message', 'RecruiterCandidates::sendMessage/$1', ['filter' => 'recruiter']);
$routes->post('recruiter/candidate/(:num)/save-notes', 'RecruiterCandidates::saveNotes/$1', ['filter' => 'recruiter']);
$routes->post('recruiter/candidate/(:num)/invite-job', 'RecruiterCandidates::inviteToJob/$1', ['filter' => 'recruiter']);
$routes->post('recruiter/candidates/invite-job/bulk', 'RecruiterCandidates::bulkInviteToJob', ['filter' => 'recruiter']);

// Notification Routes
$routes->group('notifications', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'NotificationController::index');
    $routes->get('mark-read/(:num)', 'NotificationController::markAsRead/$1');
    $routes->get('mark-all-read', 'NotificationController::markAllAsRead');
    $routes->get('delete/(:num)', 'NotificationController::delete/$1');
});

// Interview Slot Booking Routes
$routes->group('candidate', ['filter' => 'candidate'], function($routes) {
    $routes->get('book-slot/(:num)', 'SlotBookingController::bookSlot/$1');
    $routes->post('process-booking', 'SlotBookingController::processBooking');
    $routes->get('reschedule-slot/(:num)', 'SlotBookingController::rescheduleSlot/$1');
    $routes->post('process-reschedule', 'SlotBookingController::processReschedule');
    $routes->get('my-bookings', 'SlotBookingController::myBookings');
});

// Interview Slot Management Routes (Recruiter)
$routes->group('recruiter', ['filter' => 'recruiter'], function($routes) {
    
    // Slot Management
    $routes->get('slots', 'SlotManagementController::index');
    $routes->get('slots/create', 'SlotManagementController::create');
    $routes->post('slots/store', 'SlotManagementController::store');
    $routes->get('slots/edit/(:num)', 'SlotManagementController::edit/$1');
    $routes->post('slots/update/(:num)', 'SlotManagementController::update/$1');
    $routes->get('slots/delete/(:num)', 'SlotManagementController::delete/$1');
    
    // Booking Management
    $routes->get('slots/bookings', 'SlotManagementController::bookings');
    $routes->get('slots/reschedule/(:num)', 'SlotManagementController::adminReschedule/$1');
    $routes->post('slots/process-reschedule', 'SlotManagementController::processAdminReschedule');
    $routes->post('slots/mark-completed/(:num)', 'SlotManagementController::markCompleted/$1');
    $routes->get('slots/review/(:num)', 'SlotManagementController::review/$1');
    $routes->post('slots/review/(:num)', 'SlotManagementController::saveReview/$1');
    
    // Bulk Actions
    $routes->post('slots/bulk-shortlist', 'SlotManagementController::bulkShortlist');
});

// Career Chatbot Routes
$routes->group('career-chatbot', ['filter' => 'candidate'], function($routes) {
    $routes->get('/', 'CareerChatbotController::index');
    $routes->post('chat', 'CareerChatbotController::chat');
    $routes->post('book-mentor', 'CareerChatbotController::bookMentor');
});

// Premium Career Mentor Routes
$routes->get('premium/plans', 'Premium::plans', ['filter' => 'candidate']);
$routes->group('premium-mentor', ['filter' => 'candidate'], function($routes) {
    $routes->get('/', 'PremiumCareerMentorController::index');
    $routes->get('plans', 'PremiumCareerMentorController::plans');
    $routes->post('chat', 'PremiumCareerMentorController::chat');
    $routes->post('start-trial', 'PremiumCareerMentorController::startTrial');
    $routes->post('create-career-plan', 'PremiumCareerMentorController::createCareerPlan');
});

// Payment Routes (Razorpay)
$routes->group('payment', ['filter' => 'candidate'], function($routes) {
    $routes->post('create-order', 'PaymentController::createOrder');
    $routes->post('verify',       'PaymentController::verify');
    $routes->get('history',       'PaymentController::history');
});
// Razorpay webhook — no auth filter, verified by signature
$routes->post('payment/webhook', 'PaymentController::webhook');

// AI-powered MNC Job Discovery (new dedicated endpoint)
$routes->get('mnc/discover', 'MncJobController::discover', ['filter' => 'candidate']);
$routes->get('mnc/job/save/(:num)', 'MncJobController::save/$1', ['filter' => 'candidate']);
$routes->get('mnc/job/unsave/(:num)', 'MncJobController::unsave/$1', ['filter' => 'candidate']);
//$routes->get('mnc', 'MncJobController::index'); // New route to render the UI

// Company Jobs Routes
$routes->get('candidate/company-jobs/suggestions', 'CompanyJobsController::suggestions');
$routes->get('candidate/company-jobs/clear-cache/(:segment)', 'CompanyJobsController::clearCache/$1');
$routes->get('candidate/company-jobs/clear-all-cache', 'CompanyJobsController::clearAllCache');
$routes->get('candidate/company-jobs/(:segment)', 'CompanyJobsController::viewCompanyJobs/$1', ['filter' => 'candidate']);

// Google Calendar Sync Routes
$routes->group('auth', ['filter' => 'auth'], function($routes) {
    $routes->get('google-calendar/connect', 'CalendarSyncController::connect');
    $routes->get('google-calendar/callback', 'CalendarSyncController::callback');
    $routes->get('google-calendar/disconnect', 'CalendarSyncController::disconnect');
    $routes->get('google-calendar/sync/(:num)', 'CalendarSyncController::syncBooking/$1');
    $routes->get('google-calendar/test-reminder/(:num)', 'CalendarSyncController::testReminder/$1');
});

// Cron/Background job routes (no auth, but secret protected)
$routes->get('cron/reminders', 'CalendarSyncController::runReminders');
$routes->get('cron/mailboxes', 'MailboxCronController::sync');
