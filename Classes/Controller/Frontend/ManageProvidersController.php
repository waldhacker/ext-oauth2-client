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

namespace Waldhacker\Oauth2Client\Controller\Frontend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Waldhacker\Oauth2Client\Repository\FrontendUserRepository;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;
use Waldhacker\Oauth2Client\Session\SessionManager;

class ManageProvidersController extends ActionController
{
    private Oauth2ProviderManager $oauth2ProviderManager;
    private FrontendUserRepository $frontendUserRepository;
    private SessionManager $sessionManager;
    private Context $context;

    public function __construct(
        Oauth2ProviderManager $oauth2ProviderManager,
        FrontendUserRepository $frontendUserRepository,
        SessionManager $sessionManager,
        Context $context
    ) {
        $this->oauth2ProviderManager = $oauth2ProviderManager;
        $this->frontendUserRepository = $frontendUserRepository;
        $this->sessionManager = $sessionManager;
        $this->context = $context;
    }

    public function listAction(): ?ResponseInterface
    {
        $this->view->assignMultiple([
            'providers' => $this->oauth2ProviderManager->getEnabledFrontendProviders(),
            'activeProviders' => $this->frontendUserRepository->getActiveProviders()
        ]);

        $isV10Branch = $this->isV10Branch();

        /** @var ServerRequestInterface $serverRequest */
        $serverRequest = $isV10Branch ? $this->getServerRequest() : $this->request;
        $originalRequestData = [
            'protocolVersion' => $serverRequest->getProtocolVersion(),
            'method' => $serverRequest->getMethod(),
            'uri' => (string)$serverRequest->getUri(),
            'headers' => $serverRequest->getHeaders(),
            'parsedBody' => is_array($serverRequest->getParsedBody()) ? $serverRequest->getParsedBody() : [],
        ];

        $psrResponse = $isV10Branch ? null : $this->htmlResponse();
        if ($this->context->getAspect('frontend.user')->isLoggedIn()) {
            $this->sessionManager->setAndSaveSessionData(SessionManager::SESSION_NAME_ORIGINAL_REQUEST, $originalRequestData, $serverRequest);
            if ($isV10Branch) {
                $this->sessionManager->appendOAuth2CookieToExtbaseResponse($serverRequest);
            } else {
                $psrResponse = $psrResponse ? $this->sessionManager->appendOAuth2CookieToResponse($psrResponse, $serverRequest) : null;
            }
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

    private function isV10Branch(): bool
    {
        return (int)VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getCurrentTypo3Version())['version_main'] === 10;
    }

    private function getServerRequest(): ServerRequestInterface
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        if (!($request instanceof ServerRequestInterface)) {
            throw new \InvalidArgumentException(sprintf('Request must implement "%s"', ServerRequestInterface::class), 1643446511);
        }
        return $request;
    }
}
