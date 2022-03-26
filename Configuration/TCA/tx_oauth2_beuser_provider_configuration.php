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
    'ctrl' => [
        'title' => 'LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:tx_oauth2_beuser_provider_configuration',
        'label' => 'provider',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'hideTable' => 1,
        'typeicon_classes' => [
            'default' => 'actions-key'
        ],
        'enablecolumns' => [
            'be_user' => 'parentid',
        ],
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
        'rootLevel' => 1
    ],

    'columns' => [
        'parentid' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'provider' => [
            'label' => 'Provider',
            'config' => [
                'type' => 'input',
                'readOnly' => true
            ]
        ],
        'identifier' => [
            'label' => 'Identifier',
            'config' => [
                'type' => 'input',
                'readOnly' => true
            ]
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => 'provider,identifier',
        ],
    ],
];
