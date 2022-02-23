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

(static function () {
    $languageFile = 'LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', [
        'tx_oauth2_client_configs' => [
            'label' => $languageFile . 'tx_oauth2_client_config',
            'exclude' => true,
            'config' => [
                'type' => 'inline',
                'renderType' => 'oauth2providers',
                'foreign_table' => 'tx_oauth2_feuser_provider_configuration',
                'foreign_field' => 'parentid',
            ],
        ],
    ]);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users', 'tx_oauth2_client_configs', '', 'before:lastlogin');
})();
