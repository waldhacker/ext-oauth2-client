<?php

defined('TYPO3') or die();

(static function () {
    $languageFile = 'LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:';

    $GLOBALS['SiteConfiguration']['site']['columns']['enabled_oauth2_providers'] = [
        'label' => $languageFile . 'site.enabled_oauth2_providers.title',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'items' => [],
            'itemsProcFunc' => \Waldhacker\Oauth2Client\Backend\SiteConfig\ConfiguredFrontendProvidersItemsProcFunc::class . '->getItems',
            'default' => '',
        ],
    ];

    $GLOBALS['SiteConfiguration']['site']['columns']['oauth2_callback_slug'] = [
        'label' => $languageFile . 'site.oauth2_callback_slug.title',
        'description' => $languageFile . 'site.oauth2_callback_slug.description',
        'config' => [
            'type' => 'input',
            'default' => '',
        ],
    ];

    $GLOBALS['SiteConfiguration']['site']['columns']['oauth2_storage_pid'] = [
        'label' => $languageFile . 'site.oauth2_storage_pid.title',
        'description' => $languageFile . 'site.oauth2_storage_pid.description',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['', '']
            ],
            'foreign_table' => 'pages',
            'foreign_table_where' => ' AND module="fe_users" AND l10n_parent=0 ORDER BY pid, sorting',
        ],
    ];

    $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] .= '
        ,--div--;' . $languageFile . 'site.tab,
            enabled_oauth2_providers,
            oauth2_callback_slug,
            oauth2_storage_pid
    ';
})();
