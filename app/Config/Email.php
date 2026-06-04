<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail  = 'yourgmail@gmail.com';
    public string $fromName   = 'HireMatrix Test';
    public string $recipients = '';

    /**
     * The "user agent"
     */
    public string $userAgent = 'CodeIgniter';

    /**
     * The mail sending protocol: mail, sendmail, smtp
     */
    public string $protocol = 'smtp';

    /**
     * The server path to Sendmail.
     */
    public string $mailPath = '/usr/sbin/sendmail';

    /**
     * SMTP Server Hostname
     */
    public string $SMTPHost = 'smtp.gmail.com';

    /**
     * SMTP Username
     */
    public string $SMTPUser = '';

    /**
     * SMTP Password
     */
    public string $SMTPPass = '';

    /**
     * SMTP Port
     */
    public int $SMTPPort = 587;

    /**
     * SMTP Timeout (in seconds)
     */
    public int $SMTPTimeout = 30;

    /**
     * Enable persistent SMTP connections
     */
    public bool $SMTPKeepAlive = false;

    /**
     * SMTP Encryption.
     *
     * @var string '', 'tls' or 'ssl'. 'tls' will issue a STARTTLS command
     *             to the server. 'ssl' means implicit SSL. Connection on port
     *             465 should set this to ''.
     */
    public string $SMTPCrypto = 'tls';

    /**
     * Enable word-wrap
     */
    public bool $wordWrap = true;

    /**
     * Character count to wrap at
     */
    public int $wrapChars = 76;

    /**
     * Type of mail, either 'text' or 'html'
     */
    public string $mailType = 'html';

    /**
     * Character set (utf-8, iso-8859-1, etc.)
     */
    public string $charset = 'UTF-8';

    /**
     * Whether to validate the email address
     */
    public bool $validate = false;

    /**
     * Email Priority. 1 = highest. 5 = lowest. 3 = normal
     */
    public int $priority = 3;

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $CRLF = "\r\n";

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $newline = "\r\n";

    /**
     * Enable BCC Batch Mode.
     */
    public bool $BCCBatchMode = false;

    /**
     * Number of emails in each BCC batch
     */
    public int $BCCBatchSize = 200;

    /**
     * Enable notify message from server
     */
    public bool $DSN = false;

    public function __construct()
    {
        parent::__construct();

        $this->fromEmail = (string) (env('email.fromEmail') ?? env('GMAIL_FROM_EMAIL') ?? env('SES_FROM_EMAIL') ?? env('SENDGRID_FROM_EMAIL') ?? $this->fromEmail);
        $this->fromName = (string) (env('email.fromName') ?? env('GMAIL_FROM_NAME') ?? env('SES_FROM_NAME') ?? env('SENDGRID_FROM_NAME') ?? $this->fromName);
        $this->recipients = (string) (env('email.recipients') ?? $this->recipients);
        $this->protocol = (string) (env('email.protocol') ?? env('GMAIL_PROTOCOL') ?? env('SES_PROTOCOL') ?? env('SENDGRID_PROTOCOL') ?? $this->protocol);
        $this->mailPath = (string) (env('email.mailPath') ?? $this->mailPath);
        $this->SMTPHost = (string) (env('email.SMTPHost') ?? env('GMAIL_SMTP_HOST') ?? env('SES_SMTP_HOST') ?? env('SENDGRID_SMTP_HOST') ?? $this->SMTPHost);
        $this->SMTPUser = (string) (env('email.SMTPUser') ?? env('GMAIL_SMTP_USER') ?? env('SES_SMTP_USER') ?? env('SENDGRID_SMTP_USER') ?? $this->SMTPUser);
        $this->SMTPPass = (string) (env('email.SMTPPass') ?? env('GMAIL_SMTP_PASS') ?? env('SES_SMTP_PASS') ?? env('SENDGRID_SMTP_PASS') ?? env('SENDGRID_API_KEY') ?? $this->SMTPPass);
        $this->SMTPPort = (int) (env('email.SMTPPort') ?? env('GMAIL_SMTP_PORT') ?? env('SES_SMTP_PORT') ?? env('SENDGRID_SMTP_PORT') ?? $this->SMTPPort);
        $this->SMTPTimeout = (int) (env('email.SMTPTimeout') ?? $this->SMTPTimeout);
        $this->SMTPKeepAlive = filter_var(env('email.SMTPKeepAlive') ?? $this->SMTPKeepAlive, FILTER_VALIDATE_BOOLEAN);
        $this->SMTPCrypto = (string) (env('email.SMTPCrypto') ?? env('GMAIL_SMTP_CRYPTO') ?? env('SES_SMTP_CRYPTO') ?? env('SENDGRID_SMTP_CRYPTO') ?? $this->SMTPCrypto);
        $this->wordWrap = filter_var(env('email.wordWrap') ?? $this->wordWrap, FILTER_VALIDATE_BOOLEAN);
        $this->wrapChars = (int) (env('email.wrapChars') ?? $this->wrapChars);
        $this->mailType = (string) (env('email.mailType') ?? env('GMAIL_MAIL_TYPE') ?? env('SES_MAIL_TYPE') ?? env('SENDGRID_MAIL_TYPE') ?? $this->mailType);
        $this->charset = (string) (env('email.charset') ?? $this->charset);
        $this->validate = filter_var(env('email.validate') ?? $this->validate, FILTER_VALIDATE_BOOLEAN);
        $this->priority = (int) (env('email.priority') ?? $this->priority);
        $this->CRLF = (string) (env('email.CRLF') ?? $this->CRLF);
        $this->newline = (string) (env('email.newline') ?? $this->newline);
        $this->BCCBatchMode = filter_var(env('email.BCCBatchMode') ?? $this->BCCBatchMode, FILTER_VALIDATE_BOOLEAN);
        $this->BCCBatchSize = (int) (env('email.BCCBatchSize') ?? $this->BCCBatchSize);
        $this->DSN = filter_var(env('email.DSN') ?? $this->DSN, FILTER_VALIDATE_BOOLEAN);
    }
}
