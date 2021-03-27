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

namespace Waldhacker\Oauth2Client\Events;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

final class UserLookupEvent
{
    private ResourceOwnerInterface $resourceOwner;
    private ?array $userRecord;
    private string $providerId;
    private string $code;
    private string $state;

    public function __construct(string $providerId, ResourceOwnerInterface $resourceOwner, ?array $userRecord, string $code, string $state)
    {
        $this->resourceOwner = $resourceOwner;
        $this->userRecord = $userRecord;
        $this->providerId = $providerId;
        $this->code = $code;
        $this->state = $state;
    }

    /**
     * @return ResourceOwnerInterface
     */
    public function getResourceOwner(): ResourceOwnerInterface
    {
        return $this->resourceOwner;
    }

    /**
     * @return array|null
     */
    public function getUserRecord(): ?array
    {
        return $this->userRecord;
    }

    public function setUserRecord(array $userRow): void
    {
        $this->userRecord = $userRow;
    }

    public function getProviderId(): string
    {
        return $this->providerId;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }
}
