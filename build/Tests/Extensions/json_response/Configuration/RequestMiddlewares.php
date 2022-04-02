<?php

return [
    'frontend' => [
        'typo3/json-response/encoder' => [
            'target' => \TYPO3\JsonResponse\Encoder::class,
            'before' => [
                'typo3/cms-frontend/timetracker'
            ]
        ],
        'typo3/json-response/frontend-user-authentication' => [
            'target' => \TYPO3\JsonResponse\Middleware\FrontendUserHandler::class,
            'after' => [
                'typo3/cms-frontend/frontend-user-authentication'
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ],
        'typo3/json-response/backend-user-authentication' => [
            'target' => \TYPO3\JsonResponse\Middleware\BackendUserHandler::class,
            'after' => [
                'typo3/cms-frontend/backend-user-authentication'
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ],
    ],
    'backend' => [
        'typo3/json-response/encoder' => [
            'target' => \TYPO3\JsonResponse\Encoder::class,
            'before' => [
                'typo3/cms-core/verify-host-header',
                'typo3/cms-core/normalized-params-attribute'
            ]
        ],
        'typo3/json-response/backend-user-authentication' => [
            'target' => \TYPO3\JsonResponse\Middleware\BackendUserHandler::class,
            'after' => [
                'typo3/cms-backend/authentication'
            ],
            'before' => [
                'typo3/cms-backend/site-resolver',
            ],
        ],
    ]
];
