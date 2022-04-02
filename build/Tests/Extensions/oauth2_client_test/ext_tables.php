<?php

defined('TYPO3') || die();

(static function () {
    $isV10Branch = (int)\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
        \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
    )['version_main'] === 10;

    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['tx_oauth2_test_client_configs'] = [
        'label' => '',
        'type' => 'user',
        'userFunc' => \Waldhacker\Oauth2ClientTest\Backend\UserSettingsModule\ManageProvidersButtonRenderer::class . '->render',
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings(
        'tx_oauth2_test_client_configs',
        $isV10Branch ? 'after:password2' : 'after:mfaProviders'
    );
})();
