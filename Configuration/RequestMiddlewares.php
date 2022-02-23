<?php

return [
    'backend' => [
        'oauth2-before-authentication' => [
            'target' => \Waldhacker\Oauth2Client\Middleware\Backend\BeforeAuthenticationHandler::class,
            'before' => [
                'typo3/cms-backend/authentication',
            ],
            'after' => [
                'typo3/cms-backend/backend-routing',
            ],
        ],
    ],
    'frontend' => [
        'oauth2-before-authentication' => [
            'target' => \Waldhacker\Oauth2Client\Middleware\Frontend\BeforeAuthenticationHandler::class,
            'before' => [
                'typo3/cms-frontend/authentication',
            ],
            'after' => [
                'typo3/cms-frontend/site',
                'typo3/cms-frontend/maintenance-mode',
            ],
        ],
        'oauth2-after-authentication' => [
            'target' => \Waldhacker\Oauth2Client\Middleware\Frontend\AfterAuthenticationHandler::class,
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
                'typo3/cms-redirects/redirecthandler',
                'typo3/cms-adminpanel/initiator',
            ],
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
        ],
    ],
];
