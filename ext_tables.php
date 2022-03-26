<?php

defined('TYPO3') || die();

(static function () {
    $languageFile = 'LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:';

    $isV10Branch = (int)\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
        \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
    )['version_main'] === 10;

    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['tx_oauth2_client_configs'] = [
        'label' => $languageFile . 'userSettings.label',
        'type' => 'user',
        'userFunc' => \Waldhacker\Oauth2Client\Backend\UserSettingsModule\ManageProvidersButtonRenderer::class . '->render',
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings(
        'tx_oauth2_client_configs',
        $isV10Branch ? 'after:password2' : 'after:mfaProviders'
    );
})();
