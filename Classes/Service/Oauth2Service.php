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

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Oauth2Service implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    protected Oauth2ProviderManager $oauth2ProviderManager;

    public function __construct(Oauth2ProviderManager $oauth2ProviderManager)
    {
        $this->oauth2ProviderManager = $oauth2ProviderManager;
    }

    public function getAuthorizationUrl(string $providerId, ?string $callbackUrl = null): string
    {
        if (!is_array($_SESSION)) {
            @session_start();
        }
        $provider = $this->oauth2ProviderManager->createProvider($providerId, $callbackUrl);
        $authUrl = $provider->getAuthorizationUrl();
        $_SESSION['oauth2-state'] = $provider->getState();
        return $authUrl;
    }

    public function getUser(string $code, string $state, string $providerId, ?string $callbackUrl = null): ?ResourceOwnerInterface
    {
        if (!is_array($_SESSION)) {
            @session_start();
        }
        if (!isset($_SESSION['oauth2-state']) || $_SESSION['oauth2-state'] !== $state) {
            return null;
        }
        $provider = $this->oauth2ProviderManager->createProvider($providerId, $callbackUrl);
        $user = null;
        try {
            $accessToken = $provider->getAccessToken(
                'authorization_code',
                [
                    'code' => $code,
                ]
            );
            if ($accessToken instanceof AccessToken) {
                $user = $provider->getResourceOwner($accessToken);
            }
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->warning($e->getMessage());
            }
        }
        return $user;
    }
}
