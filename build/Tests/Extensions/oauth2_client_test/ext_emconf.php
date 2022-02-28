<?php

$EM_CONF[$_EXTKEY] = [
    'title'            => 'OAuth2 Client Test Extension',
    'description'      => '',
    'category'         => 'auth',
    'author'           => 'waldhacker',
    'author_email'     => 'hello@waldhacker.dev',
    'author_company'   => 'waldhacker UG (haftungsbeschränkt)',
    'state'            => 'stable',
    'uploadfolder'     => '0',
    'clearCacheOnLoad' => 1,
    'version'          => '1.0.0',
    'constraints'      => [
        'depends' => [
            'oauth2_client' => '*'
        ]
    ],
    'autoload' => [
        'psr-4' => [
            'Waldhacker\\Oauth2ClientTest\\' => 'Classes',
        ],
    ]
];
