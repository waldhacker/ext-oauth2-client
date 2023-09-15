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

namespace Waldhacker\Oauth2Client\Authentication;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Waldhacker\Oauth2Client\Backend\LoginProvider\Oauth2LoginProvider;
use Waldhacker\Oauth2Client\Events\BackendUserLookupEvent;
use Waldhacker\Oauth2Client\Events\UserLookupEvent;
use Waldhacker\Oauth2Client\Repository\BackendUserRepository;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;
use Waldhacker\Oauth2Client\Service\Oauth2Service;
use Waldhacker\Oauth2Client\Session\SessionManager;

class BackendAuthenticationService extends AbstractAuthenticationService
{
    private array $loginData = [];
    private Oauth2ProviderManager $oauth2ProviderManager;
    private Oauth2Service $oauth2Service;
    private SessionManager $sessionManager;
    private BackendUserRepository $backendUserRepository;
    private UriBuilder $uriBuilder;
    private ResponseFactoryInterface $responseFactory;
    private ?ResourceOwnerInterface $remoteUser = null;
    private string $action = '';

    public function __construct(
        Oauth2ProviderManager $oauth2ProviderManager,
        Oauth2Service $oauth2Service,
        SessionManager $sessionManager,
        BackendUserRepository $backendUserRepository,
        UriBuilder $uriBuilder,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->oauth2ProviderManager = $oauth2ProviderManager;
        $this->oauth2Service = $oauth2Service;
        $this->sessionManager = $sessionManager;
        $this->backendUserRepository = $backendUserRepository;
        $this->uriBuilder = $uriBuilder;
        $this->responseFactory = $responseFactory;
    }

    public function getUser(): ?array
    {
        $request = $this->getRequest();
        if ($this->login['status'] !== 'login') {
            return null;
        }

        $getParameters = $request->getQueryParams();
        $postParameters = is_array($request->getParsedBody()) ? $request->getParsedBody() : [];

        $loginProvider = $getParameters['loginProvider'] ?? null;
        if ($loginProvider !== Oauth2LoginProvider::PROVIDER_ID) {
            return null;
        }

        $code = (string)($getParameters['code'] ?? '');
        $state = (string)($getParameters['state'] ?? '');
        $providerIdFromPost = (string)($postParameters['oauth2-provider'] ?? '');
        $providerIdFromGet = (string)($getParameters['oauth2-provider'] ?? '');

        $this->action = !empty($providerIdFromPost) && empty($code) && empty($state)
            ? 'authorize'
            : (!empty($providerIdFromGet) && !empty($code) && !empty($state) ? 'verify' : 'invalid');

        if ($this->action === 'authorize') {
            $this->authorize($providerIdFromPost, $request);
        }
        if ($this->action === 'verify') {
            return $this->verify($providerIdFromGet, $code, $state, $request);
        }

        $this->sessionManager->removeSessionData($request);
        return null;
    }

    public function authUser(): int
    {
        if ($this->action !== 'verify') {
            return 100;
        }

        if ($this->remoteUser instanceof ResourceOwnerInterface) {
            return 200;
        }

        return -100;
    }

    public function processLoginData(array &$loginData, string $passwordTransmissionStrategy): bool
    {
        $loginData['uname'] = $loginData['uname'] ?? '';
        $loginData['uident'] = $loginData['uident'] ?? '';

        return true;
    }

    private function authorize(string $providerId, ServerRequestInterface $request): void
    {
        // no oauth2 login at all or invalid provider
        if (empty($providerId) || !$this->oauth2ProviderManager->hasBackendProvider($providerId)) {
            $this->sessionManager->removeSessionData($request);
            return;
        }

        $authorizationUrl = $this->oauth2Service->buildGetResourceOwnerAuthorizationUrl(
            $providerId,
            $this->buildCallbackUri($providerId),
            $request
        );

        $response = $this->responseFactory->createResponse(302)->withHeader('location', $authorizationUrl);
        $response = $this->sessionManager->appendOAuth2CookieToResponse($response, $request);
        throw new ImmediateResponseException($response, 1643006821);
    }

    private function verify(string $providerId, string $code, string $state, ServerRequestInterface $request): ?array
    {
        if (empty($providerId) || empty($code) || empty($state) || !$this->oauth2ProviderManager->hasBackendProvider($providerId)) {
            $this->sessionManager->removeSessionData($request);
            return null;
        }

        $provider = $this->oauth2Service->buildGetResourceOwnerProvider(
            $state,
            $providerId,
            $this->buildCallbackUri($providerId),
            $request
        );
        if ($provider === null) {
            return null;
        }
        $accessToken = $this->oauth2Service->buildGetResourceOwnerAccessToken(
            $provider,
            $code
        );
        if ($accessToken === null) {
            return null;
        }
        $this->remoteUser = $this->oauth2Service->getResourceOwner($provider, $accessToken);

        if ($this->remoteUser === null) {
            return null;
        }

        $typo3User = $this->backendUserRepository->getUserByIdentity($providerId, (string)$this->remoteUser->getId());
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);

        /** @var UserLookupEvent $legacyUserLookupEvent */
        $legacyUserLookupEvent = $eventDispatcher->dispatch(new UserLookupEvent($providerId, $this->remoteUser, $typo3User, $code, $state));
        $typo3User = $legacyUserLookupEvent->getUserRecord();

        /** @var BackendUserLookupEvent $userLookupEvent */
        $userLookupEvent = $eventDispatcher->dispatch(new BackendUserLookupEvent(
            $providerId,
            $provider,
            $accessToken,
            $this->remoteUser,
            $typo3User
        ));
        $typo3User = $userLookupEvent->getTypo3User();

        if ($typo3User === null) {
            unset($this->remoteUser);
        }

        return $typo3User;
    }

    private function buildCallbackUri(string $providerId): string
    {
        $now = (string)time();
        return (string)$this->uriBuilder->buildUriFromRoute('login', [
            'loginProvider' => Oauth2LoginProvider::PROVIDER_ID,
            'oauth2-provider' => $providerId,
            // TYPO3\CMS\Core\Authentication\BackendUserAuthentication->formfield_status
            'login_status' => 'login',
            'commandLI' => 'attempt',
        ], UriBuilder::ABSOLUTE_URL);
    }

    private function getRequest(): ServerRequestInterface
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        if (!($request instanceof ServerRequestInterface)) {
            throw new \InvalidArgumentException(sprintf('Request must implement "%s"', ServerRequestInterface::class), 1643446001);
        }
        return $request;
    }

}
