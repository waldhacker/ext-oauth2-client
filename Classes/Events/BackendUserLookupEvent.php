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

namespace Waldhacker\Oauth2Client\Events;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessTokenInterface;

final class BackendUserLookupEvent
{
    private string $providerId;
    private AbstractProvider $provider;
    private AccessTokenInterface $accessToken;
    private ResourceOwnerInterface $remoteUser;
    private ?array $typo3User;

    public function __construct(
        string $providerId,
        AbstractProvider $provider,
        AccessTokenInterface $accessToken,
        ResourceOwnerInterface $remoteUser,
        ?array $typo3User
    ) {
        $this->providerId = $providerId;
        $this->provider = $provider;
        $this->accessToken = $accessToken;
        $this->remoteUser = $remoteUser;
        $this->typo3User = $typo3User;
    }

    public function getProviderId(): string
    {
        return $this->providerId;
    }

    public function getProvider(): AbstractProvider
    {
        return $this->provider;
    }

    public function getAccessToken(): AccessTokenInterface
    {
        return $this->accessToken;
    }

    public function getRemoteUser(): ResourceOwnerInterface
    {
        return $this->remoteUser;
    }

    public function getTypo3User(): ?array
    {
        return $this->typo3User;
    }

    public function setTypo3User(array $typo3User): void
    {
        $this->typo3User = $typo3User;
    }
}
