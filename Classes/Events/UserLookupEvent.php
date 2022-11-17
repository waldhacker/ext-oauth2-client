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

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * @deprecated since version 2, will be removed in version 3. Use `BackendUserLookupEvent` instead.
 */
final class UserLookupEvent
{
    private ResourceOwnerInterface $resourceOwner;
    private ?array $userRecord;
    private string $providerId;
    private string $code;
    private string $state;

    public function __construct(
        string $providerId,
        ResourceOwnerInterface $resourceOwner,
        ?array $userRecord,
        string $code,
        string $state
    ) {
        $this->resourceOwner = $resourceOwner;
        $this->userRecord = $userRecord;
        $this->providerId = $providerId;
        $this->code = $code;
        $this->state = $state;
    }

    /**
     * @deprecated since version 2, will be removed in version 3. Use `BackendUserLookupEvent` instead.
     */
    public function getResourceOwner(): ResourceOwnerInterface
    {
        trigger_error(
            'The event `\Waldhacker\Oauth2Client\Events\UserLookupEvent` for backend user lookups is deprecated and will stop working in version 3. Use the `\Waldhacker\Oauth2Client\Events\BackendUserLookupEvent` instead.',
            E_USER_DEPRECATED
        );

        return $this->resourceOwner;
    }

    /**
     * @deprecated since version 2, will be removed in version 3. Use `BackendUserLookupEvent` instead.
     */
    public function getUserRecord(): ?array
    {
        return $this->userRecord;
    }

    /**
     * @deprecated since version 2, will be removed in version 3. Use `BackendUserLookupEvent` instead.
     */
    public function setUserRecord(array $userRow): void
    {
        trigger_error(
            'The event `\Waldhacker\Oauth2Client\Events\UserLookupEvent` for backend user lookups is deprecated and will stop working in version 3. Use the `\Waldhacker\Oauth2Client\Events\BackendUserLookupEvent` instead.',
            E_USER_DEPRECATED
        );

        $this->userRecord = $userRow;
    }

    /**
     * @deprecated since version 2, will be removed in version 3. Use `BackendUserLookupEvent` instead.
     */
    public function getProviderId(): string
    {
        trigger_error(
            'The event `\Waldhacker\Oauth2Client\Events\UserLookupEvent` for backend user lookups is deprecated and will stop working in version 3. Use the `\Waldhacker\Oauth2Client\Events\BackendUserLookupEvent` instead.',
            E_USER_DEPRECATED
        );

        return $this->providerId;
    }

    /**
     * @deprecated since version 2, will be removed in version 3. Use `BackendUserLookupEvent` instead.
     */
    public function getCode(): string
    {
        trigger_error(
            'The event `\Waldhacker\Oauth2Client\Events\UserLookupEvent` for backend user lookups is deprecated and will stop working in version 3. Use the `\Waldhacker\Oauth2Client\Events\BackendUserLookupEvent` instead.',
            E_USER_DEPRECATED
        );

        return $this->code;
    }

    /**
     * @deprecated since version 2, will be removed in version 3. Use `BackendUserLookupEvent` instead.
     */
    public function getState(): string
    {
        trigger_error(
            'The event `\Waldhacker\Oauth2Client\Events\UserLookupEvent` for backend user lookups is deprecated and will stop working in version 3. Use the `\Waldhacker\Oauth2Client\Events\BackendUserLookupEvent` instead.',
            E_USER_DEPRECATED
        );

        return $this->state;
    }
}
