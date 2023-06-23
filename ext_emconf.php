<?php

$EM_CONF[$_EXTKEY] = [
    'title'            => 'OAuth2 Client',
    'description'      => 'TYPO3 OAuth2 Login Client (backend and frontend)',
    'category'         => 'auth',
    'author'           => 'waldhacker',
    'author_email'     => 'hello@waldhacker.dev',
    'author_company'   => 'waldhacker UG (haftungsbeschränkt)',
    'state'            => 'stable',
    'uploadfolder'     => '0',
    'clearCacheOnLoad' => 1,
    'version'          => '3.0.0',
    'constraints'      => [
        'depends' => [
            'backend' => '11.5.99-12.4.99',
            'fluid' => '11.5.99-12.4.99',
            'setup' => '11.5.99-12.4.99',
            'typo3' => '11.5.99-12.4.99',
        ]
    ],
    'autoload' => [
        'psr-4' => [
            'Waldhacker\\Oauth2Client\\' => 'Classes',
        ],
    ]
];
