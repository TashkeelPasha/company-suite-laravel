<?php

/**
 * Five auth guards for Company Suite. All share Laravel's session driver but
 * point at different provider tables so a login in one role does NOT authenticate
 * the user in any other role.
 *
 * IMPORTANT: These guards are shared between the API layer (built by the backend
 * dev) and this Blade frontend. Keep the guard names stable.
 */
return [

    'defaults' => [
        'guard' => 'superadmin',
        'passwords' => 'super_admins',
    ],

    'guards' => [
        'superadmin' => [
            'driver' => 'session',
            'provider' => 'super_admins',
        ],
        'company' => [
            'driver' => 'session',
            'provider' => 'companies',
        ],
        'station' => [
            'driver' => 'session',
            'provider' => 'team_members',
        ],
        'go' => [
            'driver' => 'session',
            'provider' => 'team_members',
        ],
        'erc' => [
            'driver' => 'session',
            'provider' => 'team_members',
        ],
    ],

    'providers' => [
        'super_admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\SuperAdmin::class,
        ],
        'companies' => [
            'driver' => 'eloquent',
            'model' => App\Models\Company::class,
        ],
        'team_members' => [
            'driver' => 'eloquent',
            'model' => App\Models\TeamMember::class,
        ],
    ],

    'passwords' => [
        'super_admins' => [
            'provider' => 'super_admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
