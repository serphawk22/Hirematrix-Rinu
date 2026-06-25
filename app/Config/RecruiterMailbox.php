<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class RecruiterMailbox extends BaseConfig
{
    public array $google = [];
    public array $microsoft = [];

    public function __construct()
    {
        parent::__construct();
        $this->google = [
            'client_id' => (string) env('mailbox.google.clientId', ''),
            'client_secret' => (string) env('mailbox.google.clientSecret', ''),
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'profile_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'scopes' => 'openid email https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/gmail.readonly',
        ];
        $this->microsoft = [
            'client_id' => (string) env('mailbox.microsoft.clientId', ''),
            'client_secret' => (string) env('mailbox.microsoft.clientSecret', ''),
            'tenant' => (string) env('mailbox.microsoft.tenant', 'organizations'),
            'scopes' => 'openid email profile offline_access Mail.Read Mail.Send User.Read',
        ];
    }
}
