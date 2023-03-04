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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;
use Waldhacker\Oauth2Client\Service\Oauth2Service;
use Waldhacker\Oauth2Client\Session\SessionManager;

class AuthorizeController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private static array $allowedActions = [
        'authorize',
        'callback',
    ];
    private Oauth2ProviderManager $oauth2ProviderManager;
    private Oauth2Service $oauth2Service;
    private SessionManager $sessionManager;
    private UriBuilder $uriBuilder;
    private ResponseFactoryInterface $responseFactory;
    private Context $context;

    public function __construct(
        Oauth2ProviderManager $oauth2ProviderManager,
        Oauth2Service $oauth2Service,
        SessionManager $sessionManager,
        UriBuilder $uriBuilder,
        ResponseFactoryInterface $responseFactory,
        Context $context
    ) {
        $this->oauth2ProviderManager = $oauth2ProviderManager;
        $this->oauth2Service = $oauth2Service;
        $this->sessionManager = $sessionManager;
        $this->uriBuilder = $uriBuilder;
        $this->responseFactory = $responseFactory;
        $this->context = $context;
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $getParameters = $request->getQueryParams();
        $action = $getParameters['action'] ?? null;
        $providerId = (string)($getParameters['oauth2-provider'] ?? '');

        if (
            !$this->context->getAspect('backend.user')->isLoggedIn()
            || empty($providerId)
            || !$this->oauth2ProviderManager->hasBackendProvider($providerId)
            || !in_array($action, self::$allowedActions, true)
        ) {
            $response = $this->responseFactory->createResponse(401);

            $this->sessionManager->removeSessionData($request);
            return $this->sessionManager->appendRemoveOAuth2CookieToResponse($response, $request);
        }

        if ($action === 'callback') {
            return $this->callback();
        }
        return $this->authorize($providerId, $request);
    }

    private function authorize(string $providerId, ServerRequestInterface $request): ResponseInterface
    {
        $callbackUrl = (string)$this->uriBuilder->buildUriFromRoute(
            'oauth2_registration_authorize',
            [
                'oauth2-provider' => $providerId,
                'action' => 'callback',
            ],
            UriBuilder::ABSOLUTE_URL
        );

        $authorizationUrl = $this->oauth2Service->buildGetResourceOwnerAuthorizationUrl(
            $providerId,
            $callbackUrl,
            $request
        );

        $response = $this->responseFactory->createResponse(302)
            ->withHeader('location', $authorizationUrl);

        return $this->sessionManager->appendOAuth2CookieToResponse($response, $request);
    }

    private function callback(): ResponseInterface
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:oauth2_client/Resources/Private/Templates/Backend/Callback.html');
        $view->assign('path', PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName('EXT:oauth2_client/Resources/Public/JavaScript/callback.js')));
        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($view->render());
        return $response;
    }
}
