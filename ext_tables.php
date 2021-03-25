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

defined('TYPO3') || die();

(static function () {
    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['tx_oauth2_client_configs'] = [
        'label' => 'LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:userSettings.label',
        'type' => 'user',
        'userFunc' => \Waldhacker\Oauth2Client\SetupModule\ProviderUserFunc::class . '->render',
    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings(
        'tx_oauth2_client_configs',
        'after:mfaProviders'
    );
})();
