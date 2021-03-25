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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Waldhacker\Oauth2Client\Service\Oauth2Service;

class RegistrationController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected ModuleTemplate $moduleTemplate;
    protected UriBuilder $uriBuilder;
    private static array $allowedActions = [
        'register',
        'callback',
    ];

    private Oauth2Service $oauth2Service;
    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        UriBuilder $uriBuilder,
        Oauth2Service $oauth2Service,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->uriBuilder = $uriBuilder;
        $this->oauth2Service = $oauth2Service;
        $this->responseFactory = $responseFactory;
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $action = $queryParams['action'];
        $providerId = $queryParams['oauth2-provider'];
        if ($providerId === null || !in_array($action, self::$allowedActions, true)) {
            return $this->responseFactory->createResponse(401);
        }

        if ($action === 'callback') {
            return $this->callback();
        }
        return $this->register($providerId);
    }

    protected function register(string $providerId): ResponseInterface
    {
        $callbackUrl = (string)$this->uriBuilder->buildUriFromRoute(
            'oauth2_callback',
            [
                'oauth2-provider' => $providerId,
                'action' => 'callback',
            ],
            UriBuilder::ABSOLUTE_URL
        );
        $authUrl = $this->oauth2Service->getAuthorizationUrl($providerId, $callbackUrl);
        return $this->responseFactory->createResponse(302)
            ->withHeader('location', $authUrl);
    }

    protected function callback(): ResponseInterface
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:oauth2_client/Resources/Private/Templates/Backend/Callback.html');
        $view->assign('path', PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName('EXT:oauth2_client/Resources/Public/JavaScript/callback.js')));
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($view->render());
        return $response;
    }
}
