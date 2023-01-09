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
    ] 
];