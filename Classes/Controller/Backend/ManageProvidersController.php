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

namespace Waldhacker\Oauth2Client\Controller\Backend;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Waldhacker\Oauth2Client\Repository\BackendUserRepository;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;

class ManageProvidersController extends AbstractBackendController
{
    private Oauth2ProviderManager $oauth2ProviderManager;
    private BackendUserRepository $backendUserRepository;
    private ModuleTemplateFactory $moduleTemplateFactory;
    private UriBuilder $uriBuilder;
    private ResponseFactoryInterface $responseFactory;
    private IconFactory $iconFactory;

    public function __construct(
        Oauth2ProviderManager $oauth2ProviderManager,
        BackendUserRepository $backendUserRepository,
        ModuleTemplateFactory $moduleTemplate,
        UriBuilder $uriBuilder,
        ResponseFactoryInterface $responseFactory,
        IconFactory $iconFactory
    ) {
        $this->oauth2ProviderManager = $oauth2ProviderManager;
        $this->backendUserRepository = $backendUserRepository;
        $this->moduleTemplateFactory = $moduleTemplate;
        $this->uriBuilder = $uriBuilder;
        $this->responseFactory = $responseFactory;
        $this->iconFactory = $iconFactory;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->addButtons($request, $moduleTemplate);
        $moduleTemplate->assignMultiple([
            'providers' => $this->oauth2ProviderManager->getConfiguredBackendProviders(),
            'activeProviders' => $this->backendUserRepository->getActiveProviders()
        ]);
        return $moduleTemplate->renderResponse('Backend/ManageProviders');
    }

    private function addButtons(ServerRequestInterface $request, ModuleTemplate $moduleTemplate): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        if (($returnUrl = $this->getReturnUrl($request)) !== '') {
            $button = $buttonBar
                ->makeLinkButton()
                ->setHref($returnUrl)
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setShowLabelText(true);
            $buttonBar->addButton($button);
        }

        $reloadButton = $buttonBar
            ->makeLinkButton()
            ->setHref($request->getAttribute('normalizedParams')->getRequestUri())
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    private function getReturnUrl(ServerRequestInterface $request): string
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        if (is_array($queryParams) && isset($queryParams['returnUrl'])) {
            $returnUrl = $queryParams['returnUrl'];
        } elseif (is_array($parsedBody) && isset($parsedBody['returnUrl'])) {
            $returnUrl = $parsedBody['returnUrl'];
        } else {
            $returnUrl = '';
        }
        $returnUrl = GeneralUtility::sanitizeLocalUrl($returnUrl);

        if ($returnUrl === '' && ExtensionManagementUtility::isLoaded('setup')) {
            $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('user_setup');
        }

        return $returnUrl;
    }
}
