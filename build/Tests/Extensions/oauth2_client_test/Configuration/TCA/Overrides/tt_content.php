<?php

defined('TYPO3') or die();

call_user_func(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'oauth2ClientTest',
        'ManageProviders',
        'manage OAuth2 providers test',
        'oauth2_client_plugin_manage_providers'
    );

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['oauth2clienttest_manageproviders'] = 'pi_flexform';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        'oauth2clienttest_manageproviders',
        'FILE:EXT:oauth2_client_test/Configuration/FlexForms/Settings.xml'
    );
});
