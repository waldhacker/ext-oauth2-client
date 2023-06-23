<?php

return [
    'oauth2_registration_authorize' => [
        'path' => '/oauth2/callback/handle',
        'access' => 'public',
        'redirect' => [
            'enable' => true,
            'parameters' => [
                'oauth2-provider' => true,
                'action' => true,
                'code' => true,
                'state' => true
            ]
        ],
        'target' => \Waldhacker\Oauth2Client\Controller\Backend\Registration\AuthorizeController::class . '::handleRequest',
    ],
    'oauth2_registration_verify' => [
        'path' => '/oauth2/callback/verify',
        'target' => \Waldhacker\Oauth2Client\Controller\Backend\Registration\VerifyController::class,
    ],
    'oauth2_manage_providers' => [
        'path' => '/oauth2/manage/providers',
        'target' => \Waldhacker\Oauth2Client\Controller\Backend\ManageProvidersController::class,
    ]
];
