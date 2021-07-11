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

namespace Waldhacker\Oauth2Client\Controller;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use Waldhacker\Oauth2Client\Repository\BackendUserRepository;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;
use Waldhacker\Oauth2Client\Service\Oauth2Service;

class RegistrationFinalize extends AbstractBackendController
{
    private Oauth2Service $oauth2Service;
    private BackendUserRepository $backendUserRepository;
    private UriBuilder $uriBuilder;
    private ResponseFactoryInterface $responseFactory;
    private Oauth2ProviderManager $oauth2ProviderManager;

    public function __construct(
        Oauth2Service $oauth2Service,
        BackendUserRepository $backendUserRepository,
        UriBuilder $uriBuilder,
        ResponseFactoryInterface $responseFactory,
        Oauth2ProviderManager $oauth2ProviderManager
    ) {
        $this->oauth2Service = $oauth2Service;
        $this->backendUserRepository = $backendUserRepository;
        $this->uriBuilder = $uriBuilder;
        $this->responseFactory = $responseFactory;
        $this->oauth2ProviderManager = $oauth2ProviderManager;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        if (!is_array($parsedBody)) {
            return $this->redirectWithWarning();
        }
        $state = $parsedBody['oauth2-state'] ?? null;
        $code = $parsedBody['oauth2-code'] ?? null;
        $providerId = isset($parsedBody['oauth2-provider'])
            ? (string)$parsedBody['oauth2-provider']
            : '';

        if (
            !isset($state, $code)
            || empty($providerId)
            || !$this->oauth2ProviderManager->hasProvider($providerId)
        ) {
            return $this->redirectWithWarning();
        }

        $callbackUrl = (string)$this->uriBuilder->buildUriFromRoute(
            'oauth2_callback',
            [
                'oauth2-provider' => $providerId,
                'action' => 'callback',
            ],
            UriBuilder::ABSOLUTE_URL
        );
        $user = $this->oauth2Service->getUser($code, $state, $providerId, $callbackUrl);
        if ($user instanceof ResourceOwnerInterface) {
            $this->backendUserRepository->persistIdentityForUser($providerId, (string)$user->getId());
            $this->addFlashMessage(
                $this->getLanguageService()->sL('LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:flash.providerConfigurationAdded.description'),
                $this->getLanguageService()->sL('LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:flash.providerConfigurationAdded.title'),
                FlashMessage::OK
            );
        } else {
            return $this->redirectWithWarning();
        }

        return $this->responseFactory->createResponse(302)
        ->withHeader('location', (string)$this->uriBuilder->buildUriFromRoute('oauth2_user_manage'));
    }

    private function redirectWithWarning(): ResponseInterface
    {
        $this->addFlashMessage(
            $this->getLanguageService()->sL('LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:flash.providerConfigurationFailed.description'),
            $this->getLanguageService()->sL('LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:flash.providerConfigurationFailed.title'),
            FlashMessage::WARNING
        );
        return $this->responseFactory->createResponse(302)
            ->withHeader('location', (string)$this->uriBuilder->buildUriFromRoute('oauth2_user_manage'));
    }
}
