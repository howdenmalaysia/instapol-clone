<?php

return [
    'config' => [
        'pno' => [
            'product_id' => 10,
            'host' => env('PNO_HOST'),
            'agent_code' => env('PNO_AGENT_CODE'),
            'user_id' => env('PNO_USER_ID'),
        ],
        'bsib' => [
            'product_id' => 16,
            'agent_code' => env('SOMPO_MOTOR_AGENT_CODE'),
            'host' => env('SOMPO_MOTOR_HOST'),
            'client_id' => env('SOMPO_MOTOR_CLIENT_ID'),
            'secret_key' => env('SOMPO_MOTOR_SECRET_KEY'),
            'auth_token' => env('SOMPO_MOTOR_AUTH_TOKEN')
        ],
        'liberty' => [
            'product_id' => 15,
            'agent_code' => env('LIBERTY_MOTOR_API_AGENT_CODE'),
            'host' => env('LIBERTY_MOTOR_API_DOMAIN'),
            'secret_key' => env('LIBERTY_MOTOR_API_PASSWORD')
        ],
        'am_config' => [
            'host' => env('AMGEN_MOTOR_API_HOST'),
            'port' => env('AMGEN_MOTOR_API_PORT'),
            'client_id'=> env('AMGEN_MOTOR_API_CLIENT_ID'),
            'client_secret'=> env('AMGEN_MOTOR_API_CLIENT_SECRET'),
            'username' => env('AMGEN_MOTOR_API_USERNAME'),
            'password' => env('AMGEN_MOTOR_API_PASSWORD'),
            'java' => env('AMGEN_MOTOR_API_JAVA'),
            'encrypt_password' => env('AMGEN_MOTOR_API_ENCRYPT_PASSWORD'),
            'encrypt_salt' => env('AMGEN_MOTOR_API_ENCRYPT_SALT'),
            'encrypt_iv' => env('AMGEN_MOTOR_API_ENCRYPT_IV'),
            'encrypt_pswd_iterations' => 10,
            'encrypt_key_size' => 32,
            'channel_token' => env('AMGEN_MOTOR_API_CHANNEL_TOKEN'),
            'brand' => env('AMGEN_MOTOR_API_BRAND'),
        ],
        'zurich_config'=> [
            'host_vix' => env('ZURICH_MOTOR_API_HOST_VIX'),
            'agent_code' => env('ZURICH_MOTOR_API_AGENT_CODE'),
            'secret_key' => env('ZURICH_MOTOR_API_SECRET_KEY'),
            'participant_code' => env('ZURICH_MOTOR_API_PARTICIPANT_CODE'),
        ],
        'zurich_takaful_config'=> [
            'host_vix' => env('ZURICH_TAKAFUL_MOTOR_API_HOST_VIX'),
            'agent_code' => env('ZURICH_TAKAFUL_MOTOR_API_AGENT_CODE'),
            'secret_key' => env('ZURICH_TAKAFUL_MOTOR_API_SECRET_KEY'),
            'participant_code' => env('ZURICH_TAKAFUL_MOTOR_API_PARTICIPANT_CODE'),
        ],
        'allianz_config'=> [
            'host' => env('ALLIANZ_MOTOR_API_HOST'),
            'url_token' => env('ALLIANZ_MOTOR_API_URL_TOKEN'),
            'url' => env('ALLIANZ_MOTOR_API_URL'),
            'username' => env('ALLIANZ_MOTOR_API_USERNAME'),
            'password' => env('ALLIANZ_MOTOR_API_PASSWORD'),
        ],
        'aig_config'=> [
            'url' => env('AIG_MOTOR_API_URL'),
            'jpj' => env('AIG_MOTOR_API_JPJ'),
            'agent_code' => env('AIG_MOTOR_API_AGENT_CODE'),
            'password' => env('AIG_MOTOR_API_PASSWORD'),
        ],
    ] 
];