# HireMatrix - AI-Powered Job Portal

HireMatrix is an AI-powered career platform for candidates, recruiters, and administrators. It includes candidate onboarding, AI resume tooling, smart job discovery, recruiter hiring workflows, interview slot booking, reminders, subscriptions, and admin analytics.

## Key Features

### For Candidates
* **AI Resume Studio:** Generate job-specific or role-based resumes optimized for ATS compatibility.
* **Career Transition AI:** Build structured learning roadmaps from current to target roles.
* **AI Career Mentor:** Premium chatbot support for interview preparation and career strategy.
* **Smart Interview Booking:** Select interview slots with calendar links and email reminders.
* **Onboarding & Parsing:** Complete profile setup with resume parsing support.
* **Job Discovery:** Find matching jobs, including MNC and remote opportunities.

### For Recruiters
* **Recruiter Dashboard:** Track applications, pipelines, and candidate leaderboards.
* **Interview Management:** Create slots, track attendance, and record structured reviews.
* **Candidate Actions:** Message candidates, save private notes, and bulk shortlist or reject.
* **Performance Tracking:** View hiring velocity and recruiter activity.

### For Administrators
* **API Usage Monitoring:** Track external API usage and costs.
* **Subscription Management:** Manage premium plans and Razorpay payment records.
* **Job Feed Scheduler:** Import jobs from external sources.

## Tech Stack

* **Framework:** PHP 8.0+ / CodeIgniter 4
* **Frontend:** Bootstrap, FontAwesome, JavaScript
* **Database:** MariaDB / MySQL
* **AI Integrations:** OpenAI, optional Tavily and GitHub APIs
* **Payments:** Razorpay
* **PDF Generation:** TCPDF
* **Document Parsing:** Smalot PDF Parser, PHPWord

## Fresh Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd ai-job-portal
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Create the environment file**
   ```bash
   cp .env.example .env
   ```

   On Windows PowerShell:
   ```powershell
   Copy-Item .env.example .env
   ```

4. **Set the app URL**

   In `.env`, set `app.baseURL` to match how you run the app:
   ```env
   app.baseURL=http://localhost:8080/
   ```

   For XAMPP under `htdocs`, this may be:
   ```env
   app.baseURL=http://localhost/ai-job-portal/public/
   ```

5. **Generate the encryption key**
   ```bash
   php spark key:generate
   ```

6. **Configure the database**

   Create a MySQL/MariaDB database named `ai_job_portal1`, then import:
   ```bash
   mysql -u root -p ai_job_portal1 < database/ai_job_portal1.sql
   ```

   Update `.env` if your local database credentials differ:
   ```env
   database.default.hostname=localhost
   database.default.database=ai_job_portal1
   database.default.username=root
   database.default.password=
   database.default.DBDriver=MySQLi
   database.default.port=3306
   ```

   If you are developing against a newer schema, run migrations after importing the dump:
   ```bash
   php spark migrate
   ```

7. **Configure required integrations**

   At minimum for AI features:
   ```env
   OPENAI_API_KEY=your_openai_api_key_here
   ```

   For recruiter verification, password reset, reminders, and notifications, configure SMTP:
   ```env
   email.fromEmail=yourgmail@gmail.com
   email.fromName=HireMatrix Test
   email.protocol=smtp
   email.SMTPHost=smtp.gmail.com
   email.SMTPUser=yourgmail@gmail.com
   email.SMTPPass=your_gmail_app_password
   email.SMTPPort=587
   email.SMTPCrypto=tls
   email.mailType=html
   ```

8. **Configure optional integrations as needed**

   Payments:
   ```env
   RAZORPAY_KEY_ID=your_razorpay_key_id
   RAZORPAY_KEY_SECRET=your_razorpay_key_secret
   RAZORPAY_WEBHOOK_SECRET=your_razorpay_webhook_secret
   RAZORPAY_CURRENCY=INR
   ```

   Google sign-in and Google Calendar:
   ```env
   google.clientId=your_google_client_id
   google.clientSecret=your_google_client_secret
   google.redirectUri=http://localhost:8080/auth/google-calendar/callback
   ```

   Job/company discovery and GitHub analysis:
   ```env
   TAVILY_API_KEY=your_tavily_api_key
   GITHUB_TOKEN=your_github_token
   ```

   Admin analytics login:
   ```env
   admin.analyticsEmail=admin@local.test
   admin.analyticsPassword=admin123
   ```

9. **Check writable folders**

   Make sure the web server can write to:
   * `writable/`
   * `writable/uploads/resumes`
   * `writable/uploads/profiles`
   * `writable/uploads/intro-videos`
   * `writable/uploads/resume_versions`
   * `writable/session`
   * `writable/cache`
   * `writable/logs`

10. **Run the app**

    With CodeIgniter's local server:
    ```bash
    php spark serve
    ```

    Open `http://localhost:8080`.

    With XAMPP, start Apache and MySQL, then open the `app.baseURL` you configured, such as `http://localhost/ai-job-portal/public/`.

## Automation & Cron

Interview reminders use `cron.secret` when configured:

```env
cron.secret=your-secure-token
```

Call the endpoint from a scheduler:

```text
{{base_url}}/cron/reminders?secret=YOUR_CRON_SECRET
```

This sends 24-hour and 1-hour interview reminders.

## Recruiter Mailbox Synchronization

Recruiters can connect the same verified company address through Google Workspace or Microsoft 365 from **Recruiter Settings → Email Sync**. Configure the OAuth clients in `.env` using the `mailbox.google.*` and `mailbox.microsoft.*` keys documented in `.env.example`, and register these callbacks:

```text
{{base_url}}/recruiter/mailbox/callback/google
{{base_url}}/recruiter/mailbox/callback/microsoft
```

Run mailbox synchronization manually or schedule it every 5–10 minutes:

```bash
php spark mailboxes:sync
```

If the hosting panel cannot run CLI commands, call the HTTP cron endpoint instead. Set a strong `cron.secret` in `.env`, then schedule:

```text
{{base_url}}/cron/mailboxes?secret=YOUR_CRON_SECRET
```

For near real-time recruiter email history, run this every 1-5 minutes. IMAP/SMTP does not push replies to the portal by itself; the cron endpoint polls connected mailboxes and stores any new candidate replies.

OAuth tokens are encrypted with the configured `encryption.key`. Never remove or rotate that key without reconnecting existing mailboxes.

Private/cPanel-hosted company mailboxes can use **Other Provider (IMAP/SMTP)** in the same settings panel. Enter the secure server names and ports supplied by the mail host (normally IMAP 993 with SSL/TLS and SMTP 465 with SSL/TLS, or SMTP 587 with STARTTLS). The portal tests both logins before saving and encrypts the mailbox credential with `encryption.key`.

## Testing

Run the PHPUnit suite:

```bash
vendor/bin/phpunit
```

Refer to `tests/README.md` for test database notes.

## Project Structure

* `app/Controllers`: Candidate, recruiter, admin, payment, and integration controllers.
* `app/Libraries`: AI, parsing, calendar, reminder, and analytics services.
* `app/Models`: Database access models.
* `app/Views`: UI templates.
* `database/ai_job_portal1.sql`: Local database dump for initial setup.
* `standalone/`: Standalone static pages.
* `writable/`: Runtime cache, logs, sessions, and uploads.

## Troubleshooting

* If pages redirect incorrectly, check `app.baseURL`.
* If login/register works but emails fail, check SMTP values in `.env`.
* If premium payment fails, check `RAZORPAY_KEY_ID` and `RAZORPAY_KEY_SECRET`.
* If AI features fail, check `OPENAI_API_KEY`.
* If MNC/company discovery is empty, check `TAVILY_API_KEY`.
* If database errors mention missing columns, import `database/ai_job_portal1.sql` and run `php spark migrate`.

## License

Distributed under the MIT License.
