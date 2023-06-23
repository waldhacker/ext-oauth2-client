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

namespace Waldhacker\Oauth2Client\Controller\Frontend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use Waldhacker\Oauth2Client\Frontend\RedirectRequestService;
use Waldhacker\Oauth2Client\Repository\FrontendUserRepository;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;
use Waldhacker\Oauth2Client\Service\SiteService;
use Waldhacker\Oauth2Client\Session\SessionManager;

class ManageProvidersController extends ActionController
{
    private Oauth2ProviderManager $oauth2ProviderManager;
    private SiteService $siteService;
    private FrontendUserRepository $frontendUserRepository;
    private SessionManager $sessionManager;
    private RedirectRequestService $redirectRequestService;
    private Context $context;

    public function __construct(
        Oauth2ProviderManager $oauth2ProviderManager,
        SiteService $siteService,
        FrontendUserRepository $frontendUserRepository,
        SessionManager $sessionManager,
        RedirectRequestService $redirectRequestService,
        Context $context
    ) {
        $this->oauth2ProviderManager = $oauth2ProviderManager;
        $this->siteService = $siteService;
        $this->frontendUserRepository = $frontendUserRepository;
        $this->sessionManager = $sessionManager;
        $this->redirectRequestService = $redirectRequestService;
        $this->context = $context;
    }

    public function listAction(): ?ResponseInterface
    {
        /** @var ServerRequestInterface $serverRequest */
        $serverRequest = $this->request;

        if ($this->context->getAspect('frontend.user')->isLoggedIn() && $this->typo3UserIsWithinConfiguredStorage($serverRequest)) {
            $this->view->assignMultiple([
                'providers' => $this->oauth2ProviderManager->getEnabledFrontendProviders(),
                'activeProviders' => $this->frontendUserRepository->getActiveProviders()
            ]);
        }

        $psrResponse = $this->htmlResponse();

        if ($this->context->getAspect('frontend.user')->isLoggedIn() && $this->typo3UserIsWithinConfiguredStorage($serverRequest)) {
            $originalRequestData = $this->redirectRequestService->buildOriginalRequestData($serverRequest);
            $this->sessionManager->setAndSaveSessionData(SessionManager::SESSION_NAME_ORIGINAL_REQUEST, $originalRequestData, $serverRequest);
            $psrResponse = $psrResponse ? $this->sessionManager->appendOAuth2CookieToResponse($psrResponse, $serverRequest) : null;
        }

        return $psrResponse;
    }

    public function deactivateAction(int $providerId): void
    {
        if ($this->context->getAspect('frontend.user')->isLoggedIn()) {
            $this->frontendUserRepository->deactivateProviderByUid($providerId);
        }

        $this->redirect('list');
    }

    private function getServerRequest(): ServerRequestInterface
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        if (!($request instanceof ServerRequestInterface)) {
            throw new \InvalidArgumentException(sprintf('Request must implement "%s"', ServerRequestInterface::class), 1643446511);
        }
        return $request;
    }

    private function typo3UserIsWithinConfiguredStorage(ServerRequestInterface $request): bool
    {
        $typo3User = $request->getAttribute('frontend.user');
        if (!($typo3User instanceof FrontendUserAuthentication)) {
            return false;
        }

        /** @var Site|null $site */
        $site = $this->siteService->getSite();
        $language = $this->siteService->getLanguage();
        if ($site === null || $language === null) {
            return false;
        }

        $siteConfiguration = $site->getConfiguration();
        $languageConfiguration = $language->toArray();
        $configuredStoragePid = empty($languageConfiguration['oauth2_storage_pid'])
                      ? ($siteConfiguration['oauth2_storage_pid'] ?? null)
                      : $languageConfiguration['oauth2_storage_pid'];

        $typo3UserStoragePid = $typo3User->user['pid'] ?? null;
        if ($typo3UserStoragePid === null) {
            return false;
        }

        return (int)$typo3UserStoragePid === (int)$configuredStoragePid;
    }
}
