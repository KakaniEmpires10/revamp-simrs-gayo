<?php

return [
    'timezone' => env('TIME_ZONE_BPJS_SERVICE', config('app.timezone')),

    'antrol' => [
        'base_url' => env('API_BPJS_ANTROL'),
        'service' => env('SERVISCE_ANTROL', 'antreanrs'),
        'cons_id' => env('CONS_ID'),
        'secret_key' => env('SECRET_KEY'),
        'user_key' => env('USER_KEY_ANTROL'),
        'timeout' => (int) env('BPJS_ANTROL_TIMEOUT', 10),
        'connect_timeout' => (int) env('BPJS_ANTROL_CONNECT_TIMEOUT', 5),
        'retry_times' => (int) env('BPJS_ANTROL_RETRY_TIMES', 1),
        'retry_sleep' => (int) env('BPJS_ANTROL_RETRY_SLEEP', 250),
    ],

    'vclaim' => [
        'base_url' => env('VCLAIM_URL'),
        'service' => env('VCLAIM_SERVISCE', 'vclaim-rest'),
        'cons_id' => env('VCLAIM_CONS_ID'),
        'secret_key' => env('VCLAIM_SECRETKEY'),
        'user_key' => env('VCLAIM_USER_KEY'),
        'timeout' => (int) env('BPJS_VCLAIM_TIMEOUT', 15),
        'connect_timeout' => (int) env('BPJS_VCLAIM_CONNECT_TIMEOUT', 5),
        'retry_times' => (int) env('BPJS_VCLAIM_RETRY_TIMES', 1),
        'retry_sleep' => (int) env('BPJS_VCLAIM_RETRY_SLEEP', 250),
        'verify_ssl' => (bool) env('BPJS_VCLAIM_VERIFY_SSL', false),
        'ppk' => env('PPK_BPJS', ''),
        'facility_name' => env('NAMA_RS', env('APP_NAME', 'SIMRS')),
    ],

    'health_checks' => [
        'fingerprint_url' => env('BPJS_FINGERPRINT_URL', 'https://fp.bpjs-kesehatan.go.id/finger-rest'),
        'frista_url' => env('BPJS_FRISTA_URL', 'https://frista.bpjs-kesehatan.go.id/frista-api/'),
        'icare_url' => env('BPJS_ICARE_URL', 'https://apijkn.bpjs-kesehatan.go.id/wsihs'),
        'aplicare_url' => env('BPJS_APLICARE_URL', 'https://apijkn.bpjs-kesehatan.go.id/aplicaresws/'),
        'antrean_rs_url' => env('BPJS_ANTREAN_RS_CHECK_URL'),
        'vclaim_url' => env('BPJS_VCLAIM_CHECK_URL'),
        'lupis_url' => env('BPJS_LUPIS_URL', 'https://lupis.bpjs-kesehatan.go.id/'),
    ],
];
