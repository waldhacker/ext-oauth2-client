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

namespace Waldhacker\Oauth2ClientTest\Http\Client\Middleware;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class LogMiddleware implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @return callable(RequestInterface, array): PromiseInterface
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            $this->logger->debug('Request', [
                'request' => [
                    'protocolVersion' => $request->getProtocolVersion(),
                    'method' => $request->getMethod(),
                    'requestTarget' => $request->getRequestTarget(),
                    'uri' => (string)$request->getUri(),
                    'headers' => $request->getHeaders(),
                    'body' => (string)(clone $request)->getBody()
                ],
            ]);

            $promise = $handler($request, $options);

            return $promise->then(
                function (ResponseInterface $response): ResponseInterface {
                    $clonedResponse = clone $response;
                    $this->logger->debug('Response', [
                        'response' => [
                            'protocolVersion' => $response->getProtocolVersion(),
                            'statusCode' => $response->getStatusCode(),
                            'headers' => $response->getHeaders(),
                            'body' => (string)(clone $response)->getBody()
                        ],
                    ]);

                    return $response;
                }
            );
        };
    }
}
