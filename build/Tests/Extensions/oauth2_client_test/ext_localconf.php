<?php

defined('TYPO3') || die();

(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'oauth2ClientTest',
        'ManageProviders',
        [\Waldhacker\Oauth2ClientTest\Controller\Frontend\ManageProvidersController::class => 'list'],
        [\Waldhacker\Oauth2ClientTest\Controller\Frontend\ManageProvidersController::class => 'list']
    );

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][\Waldhacker\Oauth2ClientTest\Backend\LoginProvider\Oauth2LoginProvider::PROVIDER_ID] = [
        'provider' => \Waldhacker\Oauth2ClientTest\Backend\LoginProvider\Oauth2LoginProvider::class,
        'sorting' => 26,
        'iconIdentifier' => 'actions-key',
        'label' => 'LLL:EXT:oauth2_client_test/Resources/Private/Language/locallang_be.xlf:login.link',
    ];

    if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['Waldhacker']['Oauth2ClientTest']['Http']['Client']['Middleware']['LogMiddleware']['writerConfiguration'])) {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['Waldhacker']['Oauth2ClientTest']['Http']['Client']['Middleware']['LogMiddleware']['writerConfiguration'] = [
            \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
                \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                    'logFile' => \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/log/typo3_requests.log'
                ],
            ],
        ];
    }

    foreach (($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client']['providers'] ?? []) as $identifier => $provider) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client']['providers'][$identifier]['collaborators']['httpClient'] = \GuzzleHttp\Client::class;
    }
})();
