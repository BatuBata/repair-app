<?php

return [
    'admin_initial_password' => env('ADMIN_INITIAL_PASSWORD'),
    'platform_owner' => [
        'username' => env('PLATFORM_OWNER_USERNAME'),
        'password_hash' => env('PLATFORM_OWNER_PASSWORD_HASH'),
    ],
];
