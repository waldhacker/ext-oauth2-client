<?php

defined('TYPO3') || die();

(static function () {
    $languageFile = 'LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:';

    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['tx_oauth2_client_configs'] = [
        'label' => $languageFile . 'userSettings.label',
        'type' => 'user',
        'userFunc' => \Waldhacker\Oauth2Client\Backend\UserSettingsModule\ManageProvidersButtonRenderer::class . '->render',
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings(
        'tx_oauth2_client_configs',
        'after:mfaProviders'
    );
})();
