<?php

return [
    'config' => [
        'pno' => [
            'host' => env('PNO_HOST'),
            'agent_code' => env('PNO_AGENT_CODE'),
            'user_id' => env('PNO_USER_ID'),
        ],
        'bsib' => [
            'agent_code' => env('SOMPO_MOTOR_AGENT_CODE'),
            'host' => env('SOMPO_MOTOR_HOST'),
            'client_id' => env('SOMPO_MOTOR_CLIENT_ID'),
            'secret_key' => env('SOMPO_MOTOR_SECRET_KEY'),
            'auth_token' => env('SOMPO_MOTOR_AUTH_TOKEN')
        ],
    ] 
];