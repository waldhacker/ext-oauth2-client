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

namespace Waldhacker\Oauth2Client\Controller\Backend\Registration;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use Waldhacker\Oauth2Client\Controller\Backend\AbstractBackendController;
use Waldhacker\Oauth2Client\Repository\BackendUserRepository;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;
use Waldhacker\Oauth2Client\Service\Oauth2Service;
use Waldhacker\Oauth2Client\Session\SessionManager;

class VerifyController extends AbstractBackendController
{
    private Oauth2Service $oauth2Service;
    private BackendUserRepository $backendUserRepository;
    private SessionManager $sessionManager;
    private UriBuilder $uriBuilder;
    private ResponseFactoryInterface $responseFactory;
    private Oauth2ProviderManager $oauth2ProviderManager;
    private Context $context;

    public function __construct(
        Oauth2Service $oauth2Service,
        BackendUserRepository $backendUserRepository,
        SessionManager $sessionManager,
        UriBuilder $uriBuilder,
        ResponseFactoryInterface $responseFactory,
        Oauth2ProviderManager $oauth2ProviderManager,
        Context $context
    ) {
        $this->oauth2Service = $oauth2Service;
        $this->backendUserRepository = $backendUserRepository;
        $this->sessionManager = $sessionManager;
        $this->uriBuilder = $uriBuilder;
        $this->responseFactory = $responseFactory;
        $this->oauth2ProviderManager = $oauth2ProviderManager;
        $this->context = $context;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $postParameters = is_array($request->getParsedBody()) ? $request->getParsedBody() : [];
        if (empty($postParameters)) {
            return $this->redirectWithWarning($request);
        }
        $providerId = (string)($postParameters['oauth2-provider'] ?? '');
        $code = (string)($postParameters['oauth2-code'] ?? '');
        $state = (string)($postParameters['oauth2-state'] ?? '');

        if (
            !$this->context->getAspect('backend.user')->isLoggedIn()
            || empty($providerId)
            || empty($code)
            || empty($state)
            || !$this->oauth2ProviderManager->hasBackendProvider($providerId)
        ) {
            return $this->redirectWithWarning($request);
        }

        $callbackUrl = (string)$this->uriBuilder->buildUriFromRoute(
            'oauth2_registration_authorize',
            [
                'oauth2-provider' => $providerId,
                'action' => 'callback',
            ],
            UriBuilder::ABSOLUTE_URL
        );

        $provider = $this->oauth2Service->buildGetResourceOwnerProvider(
            $state,
            $providerId,
            $callbackUrl,
            $request
        );
        if ($provider === null) {
            return $this->redirectWithWarning($request);
        }
        $accessToken = $this->oauth2Service->buildGetResourceOwnerAccessToken(
            $provider,
            $code
        );
        if ($accessToken === null) {
            return $this->redirectWithWarning($request);
        }
        $remoteUser = $this->oauth2Service->getResourceOwner($provider, $accessToken);

        if ($remoteUser instanceof ResourceOwnerInterface) {
            try {
                $this->backendUserRepository->persistIdentityForUser($providerId, (string)$remoteUser->getId());
            } catch (\Exception $e) {
                return $this->redirectWithWarning($request);
            }
        } else {
            return $this->redirectWithWarning($request);
        }

        $this->sessionManager->removeSessionData($request);
        $this->addFlashMessage(
            $this->getLanguageService()->sL('LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:flash.providerConfigurationAdded.description'),
            $this->getLanguageService()->sL('LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:flash.providerConfigurationAdded.title'),
            ContextualFeedbackSeverity::OK
        );

        $response = $this->responseFactory
            ->createResponse(302, 'OAuth2: Done. Redirection to original requested location')
            ->withHeader('location', (string)$this->uriBuilder->buildUriFromRoute('oauth2_manage_providers'));

        return $this->sessionManager->appendRemoveOAuth2CookieToResponse($response, $request);
    }

    private function redirectWithWarning(ServerRequestInterface $request): ResponseInterface
    {
        $this->sessionManager->removeSessionData($request);
        $this->addFlashMessage(
            $this->getLanguageService()->sL('LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:flash.providerConfigurationFailed.description'),
            $this->getLanguageService()->sL('LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:flash.providerConfigurationFailed.title'),
            ContextualFeedbackSeverity::WARNING
        );

        $response = $this->responseFactory
            ->createResponse(302, 'OAuth2: Not logged in or invalid data')
            ->withHeader('location', (string)$this->uriBuilder->buildUriFromRoute('oauth2_manage_providers'));

        return $this->sessionManager->appendRemoveOAuth2CookieToResponse($response, $request);
    }
}
