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

namespace Waldhacker\Oauth2Client\Frontend;

use Psr\Http\Message\ServerRequestInterface;

class RequestStates
{
    public const CONTROLLER_ATTRIBUTE = 'oauth2.controller';
    public const ACTION_ATTRIBUTE = 'oauth2.action';
    public const CONTROLLER_LOGIN = 'login';
    public const CONTROLLER_REGISTRATION = 'registration';
    public const ACTION_LOGIN_AUTHORIZE = 'login.authorize';
    public const ACTION_LOGIN_VERIFY = 'login.verify';
    public const ACTION_LOGIN_DONE = 'login.done';
    public const ACTION_REGISTRATION_AUTHORIZE = 'registration.authorize';
    public const ACTION_REGISTRATION_VERIFY = 'registration.verify';

    public function isCurrentController(string $controllerName, ServerRequestInterface $request): bool
    {
        return $request->getAttribute(self::CONTROLLER_ATTRIBUTE, null) === $controllerName;
    }

    public function isCurrentAction(string $actionName, ServerRequestInterface $request): bool
    {
        return $request->getAttribute(self::ACTION_ATTRIBUTE, null) === $actionName;
    }

    public function setCurrentController(string $controllerName, ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withAttribute(self::CONTROLLER_ATTRIBUTE, $controllerName);
    }

    public function setCurrentAction(string $actionName, ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withAttribute(self::ACTION_ATTRIBUTE, $actionName);
    }

    public function removeCurrentController(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withoutAttribute(self::CONTROLLER_ATTRIBUTE);
    }

    public function removeCurrentAction(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withoutAttribute(self::ACTION_ATTRIBUTE);
    }
}
