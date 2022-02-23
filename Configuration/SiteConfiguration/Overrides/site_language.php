<?php

defined('TYPO3') or die();

(static function () {
    $languageFile = 'LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:';

    $GLOBALS['SiteConfiguration']['site_language']['columns']['enabled_oauth2_providers'] = [
        'label' => $languageFile . 'site.enabled_oauth2_providers.title',
        'description' => $languageFile . 'site.enabled_oauth2_providers.override_description',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'items' => [],
            'itemsProcFunc' => \Waldhacker\Oauth2Client\Backend\SiteConfig\ConfiguredFrontendProvidersItemsProcFunc::class . '->getItems',
            'multiple' => true,
            'default' => '',
        ],
    ];

    $GLOBALS['SiteConfiguration']['site_language']['types']['1']['showitem'] .= '
        ,--div--;' . $languageFile . 'site.tab,
            enabled_oauth2_providers,
    ';
})();
