<?php

return [
    'captcha' => [
        'enabled' => env('ADMIN_CAPTCHA_ENABLED', true),
        'minimum' => 1,
        'maximum' => 12,
        'expires_seconds' => 300,
    ],
    'login' => [
        'max_attempts' => 5,
        'decay_seconds' => 60,
        'lock_minutes' => 15,
    ],
];
