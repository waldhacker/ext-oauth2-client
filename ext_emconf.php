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

$EM_CONF[$_EXTKEY] = [
    'title' => 'Oauth2 Client',
    'description' => '',
    'category' => 'auth',
    'constraints' => [
        'depends' => [
            'typo3' => '11.1.0-11.5.99',
        ],
        'conflicts' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Waldhacker\\Oauth2Client\\' => 'Classes',
        ],
    ],
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Susanne Moog, Ralf Zimmermann',
    'author_email' => 'look+typo3@susi.dev, hello@waldhacker.dev',
    'author_company' => 'Waldhacker UG (haftungsbeschränkt)',
    'version' => '1.0.1',
];
