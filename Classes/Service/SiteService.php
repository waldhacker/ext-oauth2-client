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

namespace Waldhacker\Oauth2Client\Service;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\Site;
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
            '%s?%s',
            $this->buildCallbackBaseUri($request),
            \http_build_query($queryParameters)
        );
    }

    public function doesTheRemoteInstanceCallUsBack(ServerRequestInterface $request = null): bool
    {
        $request = $this->getRequest($request);
        $callbackUri = new Uri($this->buildCallbackBaseUri($request));
        return trim($request->getUri()->getPath(), '/') === trim($callbackUri->getPath(), '/');
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

    private function buildCallbackBaseUri(ServerRequestInterface $request = null): string
    {
        return sprintf(
            '%s/%s',
            $this->getBaseUri($request),
            $this->buildCallbackSlug($request)
        );
    }

    private function buildCallbackSlug(ServerRequestInterface $request = null): string
    {
        /** @var Site|null $site */
        $site = $this->getSite($request);
        $language = $this->getLanguage($request);
        if ($site === null || $language === null) {
            return self::CALLBACK_SLUG;
        }

        $siteConfiguration = $site->getConfiguration();
        $languageConfiguration = $language->toArray();
        $callbackSlug = empty($languageConfiguration['oauth2_callback_slug'])
                              ? ($siteConfiguration['oauth2_callback_slug'] ?? '')
                              : ($languageConfiguration['oauth2_callback_slug']);
        $callbackSlug = trim($callbackSlug, '/');

        return empty($callbackSlug) ? self::CALLBACK_SLUG : $callbackSlug;
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
