<?php

declare(strict_types=1);

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['BE']['cookieSameSite'] = 'lax';
$GLOBALS['TYPO3_CONF_VARS']['BE']['loginRateLimit'] = 0;
$GLOBALS['TYPO3_CONF_VARS']['FE']['loginRateLimit'] = 0;

$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client'] = array_replace_recursive(
    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client'] ?? [],
    [
        'providers' => [
            'gitlab' => [
                'label' => 'Gitlab0 FE/BE',
                'iconIdentifier' => 'oauth2-gitlab',
                'description' => 'Login with Gitlab0 FE/BE',
                'scopes' => [
                    \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_FRONTEND,
                    \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_BACKEND,
                ],
                'options' => [
                    'clientId' => '0000000000000000000000000000000000000000000000000000000000000000',
                    'clientSecret' => '0000000000000000000000000000000000000000000000000000000000000000',
                    'urlAuthorize' => 'https://gitlab0/oauth/authorize',
                    'urlAccessToken' => 'https://gitlab0/oauth/token',
                    'urlResourceOwnerDetails' => 'https://gitlab0/api/v4/user',
                    'scopes' => ['openid', 'read_user'],
                    'scopeSeparator' => ' '
                ],
            ],
            'gitlab1-fe' => [
                'label' => 'Gitlab1 FE',
                'iconIdentifier' => 'oauth2-gitlab',
                'description' => 'Login with Gitlab1 FE',
                'scopes' => [
                    \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_FRONTEND,
                ],
                'options' => [
                    'clientId' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                    'clientSecret' => '1111111111111111111111111111111111111111111111111111111111111111',
                    'urlAuthorize' => 'https://gitlab1/oauth/authorize',
                    'urlAccessToken' => 'https://gitlab1/oauth/token',
                    'urlResourceOwnerDetails' => 'https://gitlab1/api/v4/user',
                    'scopes' => ['openid', 'read_user'],
                    'scopeSeparator' => ' '
                ],
            ],
            'gitlab2-be' => [
                'label' => 'Gitlab2 BE',
                'iconIdentifier' => 'oauth2-gitlab',
                'description' => 'Login with Gitlab2 BE',
                'scopes' => [
                    \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_BACKEND,
                ],
                'options' => [
                    'clientId' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
                    'clientSecret' => '2222222222222222222222222222222222222222222222222222222222222222',
                    'urlAuthorize' => 'https://gitlab2/oauth/authorize',
                    'urlAccessToken' => 'https://gitlab2/oauth/token',
                    'urlResourceOwnerDetails' => 'https://gitlab2/api/v4/user',
                    'scopes' => ['openid', 'read_user'],
                    'scopeSeparator' => ' '
                ],
            ],
            'gitlab3-both' => [
                'label' => 'Gitlab3 FE/BE',
                'iconIdentifier' => 'oauth2-gitlab',
                'description' => 'Login with Gitlab3 FE/BE',
                'scopes' => [
                    \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_FRONTEND,
                    \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_BACKEND,
                ],
                'options' => [
                    'clientId' => 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc',
                    'clientSecret' => '3333333333333333333333333333333333333333333333333333333333333333',
                    'urlAuthorize' => 'https://gitlab3/oauth/authorize',
                    'urlAccessToken' => 'https://gitlab3/oauth/token',
                    'urlResourceOwnerDetails' => 'https://gitlab3/api/v4/user',
                    'scopes' => ['openid', 'read_user'],
                    'scopeSeparator' => ' '
                ],
            ],
            'gitlab4-fe' => [
                'label' => 'Gitlab4 FE',
                'iconIdentifier' => 'oauth2-gitlab',
                'description' => 'Login with Gitlab4 FE',
                'scopes' => [
                    \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_FRONTEND,
                ],
                'options' => [
                    'clientId' => 'dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd',
                    'clientSecret' => '4444444444444444444444444444444444444444444444444444444444444444',
                    'urlAuthorize' => 'https://gitlab4/oauth/authorize',
                    'urlAccessToken' => 'https://gitlab4/oauth/token',
                    'urlResourceOwnerDetails' => 'https://gitlab4/api/v4/user',
                    'scopes' => ['openid', 'read_user'],
                    'scopeSeparator' => ' '
                ],
            ],
            'gitlab5-be' => [
                'label' => 'Gitlab5 BE',
                'iconIdentifier' => 'oauth2-gitlab',
                'description' => 'Login with Gitlab5 BE',
                'scopes' => [
                    \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_BACKEND,
                ],
                'options' => [
                    'clientId' => 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee',
                    'clientSecret' => '5555555555555555555555555555555555555555555555555555555555555555',
                    'urlAuthorize' => 'https://gitlab5/oauth/authorize',
                    'urlAccessToken' => 'https://gitlab5/oauth/token',
                    'urlResourceOwnerDetails' => 'https://gitlab5/api/v4/user',
                    'scopes' => ['openid', 'read_user'],
                    'scopeSeparator' => ' '
                ],
            ],
            'gitlab6-both' => [
                'label' => 'Gitlab6 FE/BE',
                'iconIdentifier' => 'oauth2-gitlab',
                'description' => 'Login with Gitlab6 FE/BE',
                'scopes' => [
                    \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_FRONTEND,
                    \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_BACKEND,
                ],
                'options' => [
                    'clientId' => 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff',
                    'clientSecret' => '6666666666666666666666666666666666666666666666666666666666666666',
                    'urlAuthorize' => 'https://gitlab6/oauth/authorize',
                    'urlAccessToken' => 'https://gitlab6/oauth/token',
                    'urlResourceOwnerDetails' => 'https://gitlab6/api/v4/user',
                    'scopes' => ['openid', 'read_user'],
                    'scopeSeparator' => ' '
                ],
            ],
            'gitlab7-fe' => [
                'label' => 'Gitlab7 FE',
                'iconIdentifier' => 'oauth2-gitlab',
                'description' => 'Login with Gitlab7 FE',
                'scopes' => [
                    \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_FRONTEND,
                ],
                'options' => [
                    'clientId' => '9999999999999999999999999999999999999999999999999999999999999999',
                    'clientSecret' => '7777777777777777777777777777777777777777777777777777777777777777',
                    'urlAuthorize' => 'https://gitlab7/oauth/authorize',
                    'urlAccessToken' => 'https://gitlab7/oauth/token',
                    'urlResourceOwnerDetails' => 'https://gitlab7/api/v4/user',
                    'scopes' => ['openid', 'read_user'],
                    'scopeSeparator' => ' '
                ],
            ],
            'gitlab8-fe' => [
                'label' => 'Gitlab8 FE',
                'iconIdentifier' => 'oauth2-gitlab',
                'description' => 'Login with Gitlab8 FE',
                'scopes' => [
                    \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_FRONTEND,
                ],
                'options' => [
                    'clientId' => '7777777777777777777777777777777777777777777777777777777777777777',
                    'clientSecret' => '8888888888888888888888888888888888888888888888888888888888888888',
                    'urlAuthorize' => 'https://gitlab8/oauth/authorize',
                    'urlAccessToken' => 'https://gitlab8/oauth/token',
                    'urlResourceOwnerDetails' => 'https://gitlab8/api/v4/user',
                    'scopes' => ['openid', 'read_user'],
                    'scopeSeparator' => ' '
                ],
            ],
        ],
    ]
);
