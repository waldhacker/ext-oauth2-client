<?php

defined('TYPO3') || die();

(static function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
        'oauth2_client',
        'auth',
        \Waldhacker\Oauth2Client\Authentication\BackendAuthenticationService::class,
        [
            'title' => 'OAuth2 Authentication',
            'description' => 'OAuth2 authentication for backend users',
            'subtype' => 'getUserBE,authUserBE,processLoginDataBE',
            'available' => true,
            'priority' => 75,
            'quality' => 50,
            'os' => '',
            'exec' => '',
            'className' => \Waldhacker\Oauth2Client\Authentication\BackendAuthenticationService::class
        ]
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
        'oauth2_client',
        'auth',
        \Waldhacker\Oauth2Client\Authentication\FrontendAuthenticationService::class,
        [
            'title' => 'OAuth2 Authentication',
            'description' => 'OAuth2 authentication for frontend users',
            'subtype' => 'getUserFE,authUserFE,processLoginDataFE',
            'available' => true,
            'priority' => 75,
            'quality' => 50,
            'os' => '',
            'exec' => '',
            'className' => \Waldhacker\Oauth2Client\Authentication\FrontendAuthenticationService::class
        ]
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'oauth2Client',
        'ManageProviders',
        [\Waldhacker\Oauth2Client\Controller\Frontend\ManageProvidersController::class => 'list,deactivate'],
        [\Waldhacker\Oauth2Client\Controller\Frontend\ManageProvidersController::class => 'list,deactivate']
    );

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][\Waldhacker\Oauth2Client\Backend\LoginProvider\Oauth2LoginProvider::PROVIDER_ID] = [
        'provider' => \Waldhacker\Oauth2Client\Backend\LoginProvider\Oauth2LoginProvider::class,
        'sorting' => 25,
        'iconIdentifier' => 'actions-key',
        'label' => 'LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:login.link',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1616684029] = [
        'nodeName' => 'oauth2providers',
        'priority' => '70',
        'class' => \Waldhacker\Oauth2Client\Backend\Form\RenderType\Oauth2ProvidersElement::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][\Waldhacker\Oauth2Client\Database\Query\Restriction\Oauth2BeUserProviderConfigurationRestriction::class] = [];
    $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][\Waldhacker\Oauth2Client\Database\Query\Restriction\Oauth2FeUserProviderConfigurationRestriction::class] = [];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][1625556930] = \Waldhacker\Oauth2Client\Backend\DataHandling\DataHandlerHook::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][1625556930] = \Waldhacker\Oauth2Client\Backend\DataHandling\DataHandlerHook::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][1625556930] = \Waldhacker\Oauth2Client\Backend\DataHandling\DataHandlerHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['oauth2_client_RenameClientConfigsTableUpdateWizard20220122130120'] = \Waldhacker\Oauth2Client\Updates\RenameClientConfigsTableUpdateWizard20220122130120::class;

    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client'] = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client'] ?? [];
})();
