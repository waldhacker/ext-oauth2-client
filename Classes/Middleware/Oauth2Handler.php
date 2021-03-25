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

namespace Waldhacker\Oauth2Client\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Http\RouteDispatcher;

class Oauth2Handler implements MiddlewareInterface
{
    private RouteDispatcher $dispatcher;

    public function __construct(RouteDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute('route');
        $option = $route->getOption('_identifier');
        if ($option === 'oauth2_handler' || $option === 'oauth2_callback') {
            return $this->dispatcher->dispatch($request);
        }
        return $handler->handle($request);
    }
}
