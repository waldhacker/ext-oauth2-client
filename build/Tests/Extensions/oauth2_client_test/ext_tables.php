<?php

defined('TYPO3') || die();

(static function () {
    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['tx_oauth2_test_client_configs'] = [
        'label' => '',
        'type' => 'user',
        'userFunc' => \Waldhacker\Oauth2ClientTest\Backend\UserSettingsModule\ManageProvidersButtonRenderer::class . '->render',
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings(
        'tx_oauth2_test_client_configs',
        'after:mfaProviders'
    );
})();
