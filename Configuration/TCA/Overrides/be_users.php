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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', [
    'tx_oauth2_client_configs' => [
        'label' => 'OAuth2 Client Configs',
        'config' => [
            'type' => 'inline',
            'renderType' => 'oauth2providers',
            'foreign_table' => 'tx_oauth2_client_configs',
            'foreign_field' => 'parentid',
        ],
    ],
]);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tx_oauth2_client_configs', '', 'before:avatar');
