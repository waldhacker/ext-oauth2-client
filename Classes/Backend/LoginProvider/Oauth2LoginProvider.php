<?php

declare(strict_types=1);

/*
 * This file is part of the OAuth2 Client extension for TYPO3
 * - (c) 2021 waldhacker UG (haftungsbeschränkt)
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
use TYPO3\CMS\Fluid\View\StandaloneView;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;

class Oauth2LoginProvider implements LoginProviderInterface
{
    public const PROVIDER_ID = '1616569531';

    private Oauth2ProviderManager $oauth2ProviderManager;

    public function __construct(Oauth2ProviderManager $oauth2ProviderManager)
    {
        $this->oauth2ProviderManager = $oauth2ProviderManager;
    }

    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController)
    {
        $view->setTemplatePathAndFilename('EXT:oauth2_client/Resources/Private/Templates/Backend/Oauth2LoginProvider.html');
        $view->assign('providers', $this->oauth2ProviderManager->getConfiguredBackendProviders());
    }
}
