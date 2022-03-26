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

namespace Waldhacker\Oauth2ClientTest\Controller\Backend;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class ManageProvidersController
{
    private ModuleTemplate $moduleTemplate;
    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        ModuleTemplate $moduleTemplate,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->moduleTemplate = $moduleTemplate;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView('ManageProviders');
        $this->moduleTemplate->setContent($view->render());
        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    private function initializeView(string $templateName): ViewInterface
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths(['EXT:oauth2_client_test/Resources/Private/Templates/Backend']);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $view->setTemplate($templateName);
        return $view;
    }
}
