<?php

namespace TYPO3\JsonResponse;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\JsonResponse;

class Encoder implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
        } catch (ImmediateResponseException $exception) {
            $response = $exception->getResponse();
        }

        foreach (GlobalStates::getHeaders() as $key => $value) {
            $response = $response->withAddedHeader($key, $value);
        }

        return new JsonResponse([
            'statusCode' => $response->getStatusCode(),
            'reasonPhrase' => $response->getReasonPhrase(),
            'headers' => $response->getHeaders(),
            'body' => $response->getBody()->__toString(),
        ]);
    }
}
