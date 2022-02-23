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

namespace Waldhacker\Oauth2Client\Service;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class SiteService
{
    public const CALLBACK_SLUG = '_oauth2';

    public function getSite(ServerRequestInterface $request = null): ?SiteInterface
    {
        $tsfe = $this->getTypoScriptFrontendController();
        if ($tsfe) {
            return $tsfe->getSite();
        }
        $request = $this->getRequest($request);
        return $request->getAttribute('site', null);
    }

    public function getLanguage(ServerRequestInterface $request = null): ?SiteLanguage
    {
        $tsfe = $this->getTypoScriptFrontendController();
        if ($tsfe) {
            return $tsfe->getLanguage();
        }
        $request = $this->getRequest($request);
        return $request->getAttribute('language', null);
    }

    public function buildCallbackUri(array $queryParameters, ServerRequestInterface $request = null): string
    {
        return sprintf(
            '%s/%s?%s',
            $this->getBaseUri($request),
            self::CALLBACK_SLUG,
            \http_build_query($queryParameters)
        );
    }

    public function doesTheRemoteInstanceCallUsBack(ServerRequestInterface $request = null): bool
    {
        $request = $this->getRequest($request);

        [$uri,] = explode('?', (string)$request->getUri(), 2);
        $uriParts = explode('/', trim($uri, '/'));
        $lastPart = array_pop($uriParts);

        return $lastPart === self::CALLBACK_SLUG;
    }

    public function getBaseUri(ServerRequestInterface $request = null): string
    {
        $request = $this->getRequest($request);

        $language = $this->getLanguage($request);
        if ($language) {
            $base = (string)$language->getBase();
        } else {
            $base = sprintf('%s://%s', $request->getUri()->getScheme(), $request->getUri()->getAuthority());
        }
        return rtrim($base, '/');
    }

    private function getTypoScriptFrontendController(): ?TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'] ?? null;
    }

    private function getRequest(ServerRequestInterface $request = null): ServerRequestInterface
    {
        $request = $request ?? $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        if (!($request instanceof ServerRequestInterface)) {
            throw new \InvalidArgumentException(sprintf('Request must implement "%s"', ServerRequestInterface::class), 1643446000);
        }
        return $request;
    }
}
