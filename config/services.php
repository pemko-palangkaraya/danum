<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
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

    'bsre' => [
        'enabled' => filter_var(env('BSRE_ESIGN_ENABLED', false), FILTER_VALIDATE_BOOL),
        'client_url' => env('TTE_URL', env('BSRE_ESIGN_CLIENT_URL', '')),
        'sign_endpoint' => env('BSRE_ESIGN_SIGN_ENDPOINT', '/api/sign/pdf'),
        'http_method' => strtoupper(env('BSRE_ESIGN_HTTP_METHOD', 'POST')),
        'auth_type' => strtolower(env('BSRE_ESIGN_AUTH_TYPE', 'basic')),
        'username' => env('TTE_USERNAME', env('BSRE_ESIGN_USERNAME', '')),
        'password' => env('TTE_PASSWORD', env('BSRE_ESIGN_PASSWORD', '')),
        'auth_token' => env('BSRE_ESIGN_AUTH_TOKEN', ''),
        'auth_header' => env('BSRE_ESIGN_AUTH_HEADER', 'Authorization'),
        'auth_scheme' => env('BSRE_ESIGN_AUTH_SCHEME', 'Bearer'),
        'pdf_field' => env('BSRE_ESIGN_PDF_FIELD', 'file'),
        'passphrase_field' => env('BSRE_ESIGN_PASSPHRASE_FIELD', 'passphrase'),
        'nik_field' => env('BSRE_ESIGN_NIK_FIELD', 'nik'),
        'display_field' => env('BSRE_ESIGN_DISPLAY_FIELD', 'tampilan'),
        'display_mode' => env('BSRE_ESIGN_DISPLAY_MODE', 'visible'),
        'image_field' => env('BSRE_ESIGN_IMAGE_FIELD', 'image'),
        'image_value' => env('BSRE_ESIGN_IMAGE_VALUE', 'false'),
        'qr_link_field' => env('BSRE_ESIGN_QR_LINK_FIELD', 'linkQR'),
        'page_field' => env('BSRE_ESIGN_PAGE_FIELD', 'page'),
        'x_field' => env('BSRE_ESIGN_X_FIELD', 'xAxis'),
        'y_field' => env('BSRE_ESIGN_Y_FIELD', 'yAxis'),
        'width_field' => env('BSRE_ESIGN_WIDTH_FIELD', 'width'),
        'height_field' => env('BSRE_ESIGN_HEIGHT_FIELD', 'height'),
        'page' => (int) env('BSRE_ESIGN_PAGE', 1),
        'x' => (int) env('BSRE_ESIGN_X', 0),
        'y' => (int) env('BSRE_ESIGN_Y', 0),
        'width' => (int) env('BSRE_ESIGN_WIDTH', 0),
        'height' => (int) env('BSRE_ESIGN_HEIGHT', 0),
        'response_pdf_field' => env('BSRE_ESIGN_RESPONSE_PDF_FIELD', 'signed_pdf'),
        'response_base64_field' => env('BSRE_ESIGN_RESPONSE_BASE64_FIELD', 'signed_pdf_base64'),
        'timeout' => (int) env('BSRE_ESIGN_TIMEOUT', 30),
        'verify_peer' => filter_var(env('BSRE_ESIGN_VERIFY_PEER', true), FILTER_VALIDATE_BOOL),
        'logo_url' => env('BSRE_LOGO_URL', 'https://bsre.bssn.go.id/_nuxt/bsre-logo.qrawwVYt.png'),
        'footer_text' => env('BSRE_FOOTER_TEXT', 'Dokumen ini telah ditandatangani secara elektronik menggunakan Sertifikat Elektronik yang diterbitkan oleh BSrE-BSSN.'),
    ],

    'tsa' => [
        'url' => env('DANUM_TSA_URL', 'http://timestamp.sectigo.com/rfc3161'),
        'username' => env('DANUM_TSA_USERNAME', ''),
        'password' => env('DANUM_TSA_PASSWORD', ''),
        'certificate' => env('DANUM_TSA_CERTIFICATE', ''),
        'policy_oid' => env('DANUM_TSA_POLICY_OID', ''),
        'timeout' => (int) env('DANUM_TSA_TIMEOUT', 30),
        'verify_peer' => filter_var(env('DANUM_TSA_VERIFY_PEER', true), FILTER_VALIDATE_BOOL),
    ],

];