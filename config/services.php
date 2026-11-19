<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sso_subscription' => [
        'host' => env('SSO_SUB_HOST'),
        'client_id' => env('SSO_SUB_CLIENT_ID'),
        'client_secret' => env('SSO_SUB_CLIENT_SECRET'),
        'callback' => env('SSO_SUB_CLIENT_CALLBACK'),
        'scopes' => env('SSO_SUB_SCOPES', 'view-user'),
        'agency' => env('SSO_SUB_AGENCY', 'IDD0002'),
        'recheck_seconds' => (int) env('SSO_SUB_RECHECK_SECONDS', 60),
        'outage_grace_seconds' => (int) env('SSO_SUB_OUTAGE_GRACE_SECONDS', 1800),
    ],

];
