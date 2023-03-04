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
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Http\Uri;
use Waldhacker\Oauth2Client\Service\SiteService;

class RedirectRequestService
{
    private const REDIRECT_URI_QUERY_NAME = 'after-oauth2-redirect-uri';

    private SiteService $siteService;
    private Features $features;

    public function __construct(SiteService $siteService, Features $features)
    {
        $this->siteService = $siteService;
        $this->features = $features;
    }

    public function buildOriginalRequestData(ServerRequestInterface $request, bool $tryOverrideFromQuery = false): array
    {
        $mergedRequestedParameters = array_replace_recursive(
            $request->getQueryParams(),
            is_array($request->getParsedBody()) ? $request->getParsedBody() : []
        );

        if (
            !$this->features->isFeatureEnabled('oauth2.frontend.login.afterOauth2RedirectUriFromQuery')
            || !$tryOverrideFromQuery
            || empty($mergedRequestedParameters[self::REDIRECT_URI_QUERY_NAME])
        ) {
            return $this->buildOriginalRequestDataFromCurrentRequest($request);
        }

        try {
            $redirectUri = new Uri(urldecode($mergedRequestedParameters[self::REDIRECT_URI_QUERY_NAME]));
        } catch (\Exception $e) {
            return $this->buildOriginalRequestDataFromCurrentRequest($request);
        }

        if (!$this->isSameSite($redirectUri, $request)) {
            return $this->buildOriginalRequestDataFromCurrentRequest($request);
        }

        return [
            'protocolVersion' => $request->getProtocolVersion(),
            'method' => $request->getMethod(),
            'uri' => (string)$redirectUri,
            'headers' => $request->getHeaders(),
            'parsedBody' => [],
        ];
    }

    public function removeOauth2ParametersFromUri(string $originalUri): string
    {
        try {
            $uri = new Uri($originalUri);
        } catch (\Exception $e) {
            return $originalUri;
        }

        parse_str($uri->getQuery(), $queryParameters);
        unset($queryParameters['oauth2-provider'], $queryParameters[self::REDIRECT_URI_QUERY_NAME], $queryParameters['logintype']);
        $uri = $uri->withQuery(http_build_query($queryParameters));

        return (string)$uri;
    }

    private function isSameSite(Uri $redirectUri, ServerRequestInterface $request): bool
    {
        $baseUri = $this->siteService->getBaseUri($request);
        $redirectBaseUri = sprintf('%s://%s', $redirectUri->getScheme(), $redirectUri->getAuthority());
        return $baseUri === $redirectBaseUri;
    }

    private function buildOriginalRequestDataFromCurrentRequest(ServerRequestInterface $request): array
    {
        return [
            'protocolVersion' => $request->getProtocolVersion(),
            'method' => $request->getMethod(),
            'uri' => (string)$request->getUri(),
            'headers' => $request->getHeaders(),
            'parsedBody' => is_array($request->getParsedBody()) ? $request->getParsedBody() : [],
        ];
    }
}
