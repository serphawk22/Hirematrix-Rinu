# HireMatrix — Project Handover Guide

This document is the operational and technical handover for **HireMatrix**, an AI-powered job portal. Read it before changing integrations, database schema, authentication, payments, or scheduled tasks.

## 1. What this application does

HireMatrix is a multi-role job portal with three authenticated audiences:

| Role | Main purpose |
| --- | --- |
| Candidate | Builds a career profile, discovers jobs, applies, manages interviews, and uses AI career tools. |
| Recruiter | Represents a company, posts jobs, runs an applicant pipeline, communicates with candidates, and schedules interviews. |
| Administrator | Manages portal data, recruiter verification, external job imports, subscriptions, content, and analytics. |

The project also has public pages for the landing page, login/registration, contact, legal pages, and job-related content.

## 2. Technology stack

| Layer | Technology |
| --- | --- |
| Application | PHP 7.4+ / PHP 8+, CodeIgniter 4 (MVC) |
| Web server | Apache via XAMPP locally, or a PHP-compatible production web server |
| Database | MySQL or MariaDB |
| UI | Server-rendered PHP views, Bootstrap, JavaScript, Font Awesome |
| HTTP client | Guzzle |
| Documents | TCPDF, PHPWord, Smalot PDF Parser, PhpSpreadsheet |
| Tests | PHPUnit 9 |
| Styling build | `php tools/build-css.php` via `composer build:css` |

Dependencies are defined in `composer.json`. Do not edit `vendor/`; run Composer after dependency changes.

## 3. Repository map

| Path | Responsibility |
| --- | --- |
| `app/Controllers/` | HTTP request handling and application orchestration. |
| `app/Models/` | Database access models. |
| `app/Libraries/` | AI, external APIs, mail, parsing, calendar, payment-support, and domain services. |
| `app/Views/` | Server-rendered screens grouped by candidate, recruiter, admin, and public areas. |
| `app/Filters/` | Authentication and role-based access filters. |
| `app/Config/Routes.php` | Authoritative route list and route-level access control. |
| `app/Database/Migrations/` | Incremental schema changes. Apply after importing the base database. |
| `database/ai_job_portal1.sql` | Initial local database dump. |
| `public/` | Web root, static assets, uploaded public media, service worker. |
| `writable/` | Runtime files: logs, cache, sessions, generated/temporary documents, private uploads. Must be writable by PHP. |
| `tests/` | PHPUnit unit/database/session tests. |
| `tools/build-css.php` | CSS asset build script. |

## 4. Local setup

### Prerequisites

- PHP version compatible with the Composer lock file (PHP 8 is recommended).
- Composer.
- MySQL/MariaDB.
- Apache/PHP (XAMPP is supported) or the CodeIgniter local server.
- PHP extensions needed by the selected database driver, curl/OpenSSL, file uploads, and document libraries.

### Installation

1. Clone the repository and run `composer install`.
2. Copy `.env.example` to `.env`.
3. Generate the CodeIgniter encryption key with `php spark key:generate`.
4. Create database `ai_job_portal1` and import `database/ai_job_portal1.sql`.
5. Update database settings and `app.baseURL` in `.env`.
6. Run `php spark migrate` to apply migrations added after the SQL dump.
7. Give the PHP/web-server user write access to `writable/` and its child directories.
8. Start with `php spark serve`, or configure Apache's document root to `public/`.

For XAMPP under `htdocs`, a typical base URL is `http://localhost/ai-job-portal/public/`.

### Required writable directories

- `writable/uploads/resumes`
- `writable/uploads/profiles`
- `writable/uploads/intro-videos`
- `writable/uploads/resume_versions`
- `writable/cache`
- `writable/logs`
- `writable/session`

## 5. Configuration and secrets

Never commit a populated `.env` file. The important variables are:

| Configuration | Used by | Required when |
| --- | --- | --- |
| `app.baseURL` | URL generation and OAuth callbacks | Always |
| `database.default.*` | Application database | Always |
| `encryption.key` | Encryption of sensitive stored values | Always in production; do not rotate casually |
| `OPENAI_API_KEY` | Resume, matching, career, and chatbot features | AI features enabled |
| `AI_INTERVIEW_OPENAI_API_KEY`, `MISTRAL_API_KEY` | Optional AI interview/provider workflows | Relevant feature enabled |
| `TAVILY_API_KEY` | External company/job discovery | Discovery enabled |
| `GITHUB_TOKEN` | Candidate GitHub analysis | GitHub analysis enabled |
| `RAZORPAY_*` | Premium payment orders, verification, and webhooks | Payments enabled |
| `email.*` / `GMAIL_*` | Transactional email and reminders | Email enabled |
| `google.*` | Candidate Google sign-in/calendar | Google auth/calendar enabled |
| `mailbox.google.*`, `mailbox.microsoft.*` | Recruiter mailbox OAuth | Mailbox sync enabled |
| `cron.secret` | HTTP cron endpoints | Scheduled HTTP tasks enabled |
| `admin.analytics*` | Administrator analytics login | Admin access |

Treat `encryption.key` as persistent infrastructure. Existing encrypted OAuth tokens and custom mailbox credentials cannot be recovered after changing it; users would need to reconnect their mailboxes.

### Current subscription/payment status: hidden

Subscriptions and payment processing are **currently disabled in the product UI**. The application constant is set in `app/Config/Constants.php`:

```php
defined('SUBSCRIPTIONS_ENABLED') || define('SUBSCRIPTIONS_ENABLED', false);
```

With this value set to `false`:

- Candidate Premium Plans navigation and dashboard upgrade promotions are hidden.
- Admin subscription navigation and subscription/revenue dashboard widgets are hidden.
- Premium-gated features are treated as available to candidates without an active subscription.
- The subscription/payment code, database tables, routes, plan screens, payment history screen, and Razorpay integration remain in the repository for future use.

Do not enable subscriptions merely by changing this constant. Follow the enablement checklist in the Payments section after live Razorpay credentials, plan data, legal terms, webhook configuration, and end-to-end testing are ready.

## 6. Roles, access control, and authentication

Route protection is configured in `app/Config/Routes.php`; filter aliases are defined in `app/Config/Filters.php`.

| Filter | Meaning |
| --- | --- |
| `auth` | Any signed-in user |
| `candidate` | Candidate-only area |
| `recruiter` | Recruiter-only area |
| `admin` | Administrator-only area |
| `*_csrf` | The relevant role filter plus CSRF validation |

Authentication supports normal login, password reset, password change, candidate Google sign-in, and remember-login tokens. Recruiter registration includes verification workflows. Do not expose recruiter candidate-contact routes without retaining the recruiter access checks.

CSRF settings are environment-driven. State-changing endpoints should use POST and an appropriate CSRF filter. During feature work, review routes carefully: some legacy GET actions exist and should not be copied into new functionality.

## 7. Functional map

### 7.1 Candidate experience

- Account registration, login, Google sign-in, password reset, and settings.
- Multi-step onboarding that can parse an uploaded resume.
- Profile management: personal and career details, skills, work history, education, certifications, projects, interests, profile photo, intro video, preferences, privacy, and notification channels.
- Resume Studio: upload/parse a resume, generate AI/ATS-oriented resumes, manage multiple versions, designate a primary version, preview, download, and delete versions.
- GitHub analysis to enrich a candidate's skills/profile.
- Job search, job detail view, applications, application withdrawal, saved jobs, job-visit tracking, smart job suggestions, and job alerts.
- External/MNC and company-specific job discovery.
- Company profiles and candidate company reviews.
- Candidate dashboard: application status, AI cover letter, ATS match analysis, search strategy, mock interview links, messages, blogs, feedback, and notifications.
- Career Transition AI: create/restart transition plans, modules/lessons/tasks, course progression, history, and PDF export.
- Interview-slot booking/rescheduling, booking history, calendar links, and automated reminder emails.
- Career chatbot, premium mentor tools, paid subscriptions, and payment history.

> **Current availability note:** the subscription/payment UI is hidden because `SUBSCRIPTIONS_ENABLED` is `false`. Premium capability remains implemented but is not an active product offering.

### 7.2 Recruiter experience

- Recruiter registration/verification, company profile, employer branding, and office-tour assets.
- Job creation/editing/preview and job response pages. Jobs support salary range, application deadline, experience level, internship fields, and AI interview policy.
- Applicant pipeline: status changes, shortlist/reject/hold, follow-up scheduling, stage history, inline notes, communication outcomes, and bulk actions.
- Dashboard statistics, job/candidate leaderboards, reports, and Excel export.
- Candidate search database (**Resdex**): search candidates, profile/resume view, recruiter notes, messages, job invitations, bulk invitations, folders, and saved searches.
- Bulk email and recruiter-candidate communication history.
- Interview-slot management: bulk slot creation, capacity, filters, booking view, recruiter rescheduling, completion, candidate review, and bulk shortlisting.
- Recruiter AI chatbot and AI reports.
- Mailbox sync: Google Workspace, Microsoft 365, or custom IMAP/SMTP connection; synced candidate email activities; manual polling/sync; disconnect.

### 7.3 Administrator experience

- Admin login, operational dashboard, and API usage/cost analytics.
- User list and recruiter verification.
- Company management, template download/import, enrichment, and company ATS mappings.
- Job monitoring, manual feed import, import statistics, and job suggestions.
- Subscription/order monitoring.
- Blog creation/editing/deletion.
- Feedback and suggestion review.

## 8. Important request flows

```text
Candidate -> profile/resume -> job discovery -> application -> recruiter pipeline
          -> AI match/coach    -> interview booking -> reminders/calendar -> review

Recruiter -> company/job -> pipeline/Resdex -> message or invite -> interview slots -> outcome

Admin -> user/company/job data + import controls + subscription/API analytics
```

Primary controllers to begin investigating are `CandidateDashboardController`, `Candidate`, `Recruiter`, `JobResponsesController`, `RecruiterCandidates`, `SlotManagementController`, `PaymentController`, and `AdminAnalytics`.

## 9. Database overview

The SQL dump is the base schema and migrations evolve it. A new environment must import the dump **then** run migrations. Never assume the dump alone is current.

| Domain | Main tables |
| --- | --- |
| Accounts/profiles | `users`, `candidate_profiles`, `recruiter_profiles`, `companies`, `work_experiences`, `education`, `certifications`, `candidate_skills`, `candidate_projects`, `candidate_interests` |
| Jobs/applications | `jobs`, `applications`, `saved_jobs`, `job_suggestions`, `job_alerts`, `job_alert_deliveries`, `candidate_job_visits`, `stage_history` |
| Candidate career | `candidate_resume_versions`, `candidate_github_stats`, `career_goals`, `career_transitions`, `course_modules`, `course_lessons`, `daily_tasks` |
| Interviewing | `interview_slots`, `interview_bookings`, `interview_booking_reviews`, `interview_results`, `reschedule_history`, `ai_interview_questions` |
| Recruiter workflow | `recruiter_candidate_actions`, `recruiter_candidate_messages`, `recruiter_candidate_notes`, `recruiter_job_invitations`, `recruiter_application_workflows`, `recruiter_communication_outcomes`, `recruiter_workflow_settings` |
| Resdex | `resdex_folders`, `resdex_folder_candidates`, `resdex_saved_searches` |
| Mailbox | `recruiter_mailbox_connections`, `recruiter_email_activities` |
| Billing | `subscription_plans`, `user_subscriptions`, `payment_orders` |
| Platform | `notifications`, `feedback`, `blog_posts`, `company_reviews`, `admin_api_usage_logs`, `remember_login_tokens` |

The current application-status values include: `pending`, `applied`, `ai_interview_started`, `ai_interview_completed`, `shortlisted`, `hold`, `filtered_out`, `rejected`, `interview_slot_booked`, `selected`, `hired`, and `withdrawn`. Preserve history and transition rules when adding statuses.

### Schema-change rules

1. Add a timestamped migration under `app/Database/Migrations/`.
2. Make `up()` safe for deployment against an already-evolved database when feasible.
3. Supply a safe `down()` migration where possible.
4. Update the initial SQL dump only when the team intentionally maintains it as a fresh-install baseline.
5. Test migration against a copy of production-like data before deployment.

## 10. AI and external services

| Service/library | Code area | Purpose |
| --- | --- | --- |
| OpenAI | `AiResumeBuilder`, `AiResumeCoach`, `AiCandidateMatcher`, `AiJobMatcher`, chat/coach services | Resume generation, matching, chat, strategy, interview/career assistance |
| Tavily | company/job discovery services | External discovery and enrichment |
| GitHub | `GithubAnalyzer` | Candidate profile/skills analysis |
| Razorpay | `PaymentController` | Premium orders, payment verification, webhook processing |
| Google | `CalendarSyncController`, mailbox service | Login/calendar and mailbox OAuth |
| Microsoft | recruiter mailbox service | Microsoft 365 mailbox OAuth |
| IMAP/SMTP | `CustomMailboxClient` | Custom company mailbox connection and polling |

AI and discovery responses are external, variable, and billable. Validate all inputs, handle timeouts/failures, log usage safely, and do not render external text as trusted HTML. Job description and external job text are handled through normalization/sanitization services for this reason.

## 11. Background jobs and cron

### Interview reminders

Endpoint: `GET /cron/reminders?secret=YOUR_CRON_SECRET`

It sends scheduled interview reminders, intended for 24-hour and 1-hour windows. Schedule it frequently enough that both windows are detected reliably.

### Recruiter mailbox synchronization

Preferred command: `php spark mailboxes:sync`

HTTP alternative: `GET /cron/mailboxes?secret=YOUR_CRON_SECRET`

Run every 1–10 minutes according to desired mailbox freshness and provider limits. This is polling; IMAP/SMTP does not automatically push incoming replies into the portal.

### Job ingestion

The codebase includes external-job ingestion/scraping, normalizing, integrity-checking, and MNC-job services. Any scheduler that invokes these must be configured with rate limits, observability, and source/provider credentials. Validate output before publishing it to candidates.

Protect all cron routes with a long, unique `cron.secret`, restrict them at the network layer if possible, and monitor scheduler failures.

## 12. Payments

### Current state

The payment/subscription feature is **dormant/hidden**, not removed. `SUBSCRIPTIONS_ENABLED` is currently `false` in `app/Config/Constants.php`. Do not advertise, link to, or accept production payments until it is deliberately enabled.

Candidate premium purchases follow this lifecycle:

```text
Candidate selects plan -> create Razorpay order -> client payment -> server verification
-> payment order marked paid -> subscription activated -> history/admin reporting
```

The Razorpay webhook is intentionally unauthenticated because Razorpay calls it. It must be protected by signature verification using `RAZORPAY_WEBHOOK_SECRET`. Test both successful payment verification and duplicate webhook delivery before production releases.

### Subscription enablement checklist

Before changing `SUBSCRIPTIONS_ENABLED` to `true`:

1. Confirm `subscription_plans` contains reviewed, active plan records with correct INR amounts and durations.
2. Set live `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`, and `RAZORPAY_CURRENCY` values in the production secret store.
3. Configure Razorpay's live webhook for `POST /payment/webhook` and verify its signature validation.
4. Test create-order, payment success, payment failure, signature verification, webhook retry/duplicate delivery, subscription activation, expiry, and payment history in a non-production environment first.
5. Confirm the pricing, cancellation/refund process, terms of service, privacy disclosures, support contact, invoices/tax handling, and customer communications are approved.
6. Back up the database and monitor `payment_orders`, `user_subscriptions`, logs, and admin analytics at launch.
7. Only then set `SUBSCRIPTIONS_ENABLED` to `true`, deploy, and smoke-test the candidate and administrator UI.

To keep payments hidden, leave the constant set to `false`; it is preferable to removing code or credentials because the feature may be enabled later.

## 13. Email, calendar, and mailbox operations

- SMTP settings control transactional mail, verification messages, reset mail, and interview reminders.
- Candidate calendar routes connect/disconnect Google Calendar and can sync an interview booking.
- Mailbox tokens/custom credentials are encrypted at rest using the CodeIgniter encryption key.
- Connect redirect URIs must exactly match the deployed domain and routes, especially after any base URL change.
- Test one outbound email, one reminder, one calendar connection, and one mailbox sync after credentials or infrastructure change.

## 14. Assets and UI workflow

- Layouts are in `app/Views/Layouts/`.
- Role-specific templates are in `app/Views/candidate/`, `app/Views/recruiter/`, and `app/Views/admin/`.
- Static assets are under `public/jobboard/`.
- Build CSS after changing source stylesheets: `composer build:css`.
- Candidate and recruiter layouts use the compiled/minified CSS assets; test the affected responsive screens after rebuilding.

Do not store private resumes or sensitive exports in publicly accessible locations unless the access model explicitly requires it.

## 15. Testing and verification

Run the suite:

```powershell
vendor\bin\phpunit
```

Useful verification before handover or deployment:

1. Run migrations on a clean database and a representative upgraded database.
2. Run PHPUnit.
3. Build CSS if styles changed.
4. Smoke-test each role's login and access restrictions.
5. Test candidate application, recruiter status change, and interview booking.
6. Test an AI failure path without exposing secret/error details.
7. Test Razorpay verification/webhook in sandbox before live keys.
8. Confirm cron endpoint authentication and that reminders/mailbox sync write expected logs.

`phpunit.xml.dist` has an optional test-database configuration. Never run destructive database tests against production data.

## 16. Logging, support, and troubleshooting

- Runtime logs: `writable/logs/`.
- Session/cache/temp files: `writable/`.
- API usage data: admin analytics tables/dashboard.

| Symptom | First checks |
| --- | --- |
| Redirects or broken generated links | `app.baseURL`, web-server rewrite rules, correct `public/` document root |
| Login works but email does not | SMTP credentials, SMTP port/TLS, sender address, logs |
| AI feature fails | API key, usage/quota, network access, error logs |
| Payments fail | Razorpay key pair, order creation response, webhook secret/signature, currency |
| Discovery is empty | Tavily key, provider limits, ingestion job/logs |
| Missing column/table error | Imported the correct dump, then ran all migrations |
| Mailbox replies missing | OAuth/token status, encryption key continuity, cron schedule, mailbox logs |

## 17. Production checklist

- Set `CI_ENVIRONMENT=production`; do not expose debug toolbar/errors.
- Use HTTPS and `app.forceGlobalSecureRequests=true` where appropriate.
- Set strong, unique database, admin, SMTP, API, webhook, encryption, and cron secrets.
- Set correct OAuth callback URLs for the live domain.
- Use managed database backups and test restoration.
- Make `writable/` writable but not publicly listable; use least-privilege file permissions.
- Configure log rotation, monitoring, and alerting for cron/API/payment failures.
- Restrict database access and public cron endpoints at the network layer.
- Review upload size/type limits and storage capacity for resumes and videos.
- Confirm privacy/retention policies for candidate profiles, resumes, interview material, and synced email.

## 18. Known maintenance cautions

- The repository contains a historical SQL dump plus later migrations. Both need intentional maintenance.
- This is a server-rendered CodeIgniter application, not a separate SPA/API backend. Keep controllers, models, views, routes, and filters aligned.
- Some routes/controllers are labelled legacy for backwards compatibility. Retain redirects or update bookmarks deliberately when refactoring.
- External jobs, AI output, uploaded documents, and mailbox content are untrusted inputs; preserve validation and sanitization boundaries.
- Recruiter mail, candidate contact data, payment records, and AI prompts may contain personal data. Follow least-access and privacy obligations.
- A test database should be separate from local/prod data.

## 19. Suggested first-week onboarding for the next developer

1. Set up the application locally using this guide and verify candidate, recruiter, and admin logins.
2. Read `Routes.php`, then trace one flow per role from route to controller, model/service, and view.
3. Review `.env.example` and identify which third-party services are active in the target environment.
4. Run the test suite and CSS build.
5. Confirm database migration state and the cron/mailbox schedules.
6. Review `writable/logs/`, recent payment records, API usage, and failed scheduled tasks before changing production behavior.

## 20. Ownership handover information to fill in

Keep this section current for every environment. Do not put secret values here.

| Item | Production value/owner |
| --- | --- |
| Production URL | _Fill in_ |
| Hosting/server owner | _Fill in_ |
| Database backup location and owner | _Fill in_ |
| Domain/DNS owner | _Fill in_ |
| SMTP provider owner | _Fill in_ |
| OpenAI billing owner | _Fill in_ |
| Razorpay account owner | _Fill in_ |
| Google OAuth project owner | _Fill in_ |
| Microsoft OAuth app owner | _Fill in_ |
| Cron scheduler location | _Fill in_ |
| Error/uptime monitoring | _Fill in_ |
| Release/deployment procedure | _Fill in_ |
