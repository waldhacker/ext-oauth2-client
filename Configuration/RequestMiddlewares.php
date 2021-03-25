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
    'backend' => [
        'oauth2-handler' => [
            'target' => \Waldhacker\Oauth2Client\Middleware\Oauth2Handler::class,
            'before' => [
                'typo3/cms-backend/authentication',
            ],
            'after' => [
                'typo3/cms-backend/backend-routing',
            ],
        ],
    ],
];
