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
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
        'oauth2_client',
        'auth',
        \Waldhacker\Oauth2Client\Service\LoginService::class,
        [
            'title' => 'OAuth2 Authentication',
            'description' => 'OAuth2 authentication for backend users',
            'subtype' => 'getUserBE,authUserBE',
            'available' => true,
            'priority' => 75,
            'quality' => 50,
            'os' => '',
            'exec' => '',
            'className' => \Waldhacker\Oauth2Client\Service\LoginService::class
        ]
    );

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1616569531] = [
        'provider' => \Waldhacker\Oauth2Client\LoginProvider\Oauth2LoginProvider::class,
        'sorting' => 25,
        'icon-class' => 'fa-key',
        'label' => 'LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:login.link',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1616684029] = [
        'nodeName' => 'oauth2providers',
        'priority' => '70',
        'class' => \Waldhacker\Oauth2Client\Form\RenderType\Oauth2ProvidersElement::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][\Waldhacker\Oauth2Client\Database\Query\Restriction\Oauth2ClientConfigBackendRestriction::class] = [];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][1625556930] =
        \Waldhacker\Oauth2Client\DataHandling\DataHandlerHook::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][1625556930] =
        \Waldhacker\Oauth2Client\DataHandling\DataHandlerHook::class;
})();
