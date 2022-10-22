<?php

declare(strict_types=1);

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

namespace Waldhacker\Oauth2Client\Backend\LoginProvider;

use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;

class Oauth2LoginProvider implements LoginProviderInterface
{
    public const PROVIDER_ID = '1616569531';

    private Oauth2ProviderManager $oauth2ProviderManager;

    protected ConfigurationManager $configurationManager;

    protected TypoScriptService $typoScriptService;

    public function __construct(
        Oauth2ProviderManager $oauth2ProviderManager,
        ConfigurationManager $configurationManager,
        TypoScriptService $typoScriptService
    ) {
        $this->oauth2ProviderManager = $oauth2ProviderManager;
        $this->configurationManager = $configurationManager;
        $this->typoScriptService = $typoScriptService;
    }

    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController)
    {
        $configuration = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $typoScript = $this->typoScriptService->convertTypoScriptArrayToPlainArray($configuration);

        $view->setTemplateRootPaths($typoScript['plugin']['tx_oauth2client']['view']['templateRootPaths']);
        $view->setTemplate('Backend/Oauth2LoginProvider');

        $view->assign('providers', $this->oauth2ProviderManager->getConfiguredBackendProviders());
    }
}
