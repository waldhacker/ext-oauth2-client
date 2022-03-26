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

namespace Waldhacker\Oauth2ClientTest\Http\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Waldhacker\Oauth2ClientTest\Http\Client\Middleware\LogMiddleware;

class GuzzleClientFactory
{
    public static function getClient(): ClientInterface
    {
        $httpOptions = $GLOBALS['TYPO3_CONF_VARS']['HTTP'];
        $httpOptions['verify'] = filter_var($httpOptions['verify'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $httpOptions['verify'];

        if (isset($GLOBALS['X_TYPO3_TESTING_FRAMEWORK']['HTTP']['mocks']['className'])) {
            $mock = GeneralUtility::makeInstance(
                $GLOBALS['X_TYPO3_TESTING_FRAMEWORK']['HTTP']['mocks']['className'],
                $GLOBALS['X_TYPO3_TESTING_FRAMEWORK']['HTTP']['mocks']['options'] ?? []
            );
            $stack = MockHandler::createWithMiddleware($mock->getResponseQueue());
        } else {
            $stack = HandlerStack::create();
        }

        $logMiddleware = GeneralUtility::makeInstance(LogMiddleware::class);
        $stack->unshift($logMiddleware, 'logger');

        if (isset($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']) && is_array($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] ?? [] as $handler) {
                $stack->push($handler);
            }
        }

        $httpOptions['handler'] = $stack;
        return GeneralUtility::makeInstance(Client::class, $httpOptions);
    }
}
