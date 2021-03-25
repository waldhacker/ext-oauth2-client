<?php

/*
 * This file is part of the OAuth2 Client extension for TYPO3
 * - (c) 2021 Waldhacker UG
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

return [
    'oauth2_callback' => [
        'path' => '/oauth2/callback/handle',
        'access' => 'public',
        'target' => \Waldhacker\Oauth2Client\Controller\RegistrationController::class . '::handleRequest',
    ],
    'oauth2_handler' => [
        'path' => '/oauth2/callback',
        'access' => 'public',
        'target' => \Waldhacker\Oauth2Client\Controller\RegistrationController::class . '::handleRequest',
    ],
    'oauth2_verify' => [
        'path' => '/oauth2/verify',
        'target' => \Waldhacker\Oauth2Client\Controller\RegistrationFinalize::class,
    ],
    'oauth2_user_manage' => [
        'path' => '/oauth2/user/manage',
        'target' => \Waldhacker\Oauth2Client\Controller\ShowBackendModule::class,
    ]
];
