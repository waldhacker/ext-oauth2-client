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
    'version'          => '2.1.0',
    'constraints'      => [
        'depends' => [
            'backend' => '10.4.0-11.5.99',
            'fluid' => '10.4.0-11.5.99',
            'setup' => '10.4.0-11.5.99',
            'typo3' => '10.4.0-11.5.99',
        ]
    ],
    'autoload' => [
        'psr-4' => [
            'Waldhacker\\Oauth2Client\\' => 'Classes',
        ],
    ]
];
