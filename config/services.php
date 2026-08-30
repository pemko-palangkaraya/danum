<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have a
    | conventional file to locate the service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'libreoffice' => [
        'binary' => env('DANUM_LIBREOFFICE_BINARY'),
    ],

    'tsa' => [
        // Sectigo exposes an RFC 3161 TSA over HTTPS and supports SHA-256.
        // Keep this configurable so production can use an institutional TSA.
        'url' => env('DANUM_TSA_URL', 'https://timestamp.sectigo.com'),
        'username' => env('DANUM_TSA_USERNAME', ''),
        'password' => env('DANUM_TSA_PASSWORD', ''),
        'certificate' => env('DANUM_TSA_CERTIFICATE', ''),
        'policy_oid' => env('DANUM_TSA_POLICY_OID', ''),
        'timeout' => (int) env('DANUM_TSA_TIMEOUT', 30),
        'verify_peer' => filter_var(env('DANUM_TSA_VERIFY_PEER', true), FILTER_VALIDATE_BOOL),
    ],

];
