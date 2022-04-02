<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
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

namespace Waldhacker\Oauth2Client\Session\v10;

class UserSession
{
    protected const SESSION_UPDATE_GRACE_PERIOD = 61;
    protected string $identifier;
    protected ?int $userId;
    protected int $lastUpdated;
    protected array $data;
    protected bool $wasUpdated = false;
    protected string $ipLock = '';
    protected bool $isNew = true;
    protected bool $isPermanent = false;

    protected function __construct(string $identifier, int $userId, int $lastUpdated, array $data = [])
    {
        $this->identifier = $identifier;
        $this->userId = $userId > 0 ? $userId : null;
        $this->lastUpdated = $lastUpdated;
        $this->data = $data;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getLastUpdated(): int
    {
        return $this->lastUpdated;
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        if ($key === '') {
            throw new \InvalidArgumentException('Argument key must not be empty', 1484312516);
        }
        if ($value === null) {
            unset($this->data[$key]);
        } else {
            $this->data[$key] = $value;
        }
        $this->wasUpdated = true;
    }

    public function hasData(): bool
    {
        return $this->data !== [];
    }

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function overrideData(array $data): void
    {
        if ($this->data !== $data) {
            $this->wasUpdated = true;
        }

        $this->data = $data;
    }

    public function dataWasUpdated(): bool
    {
        return $this->wasUpdated;
    }

    public function isAnonymous(): bool
    {
        return $this->userId === 0 || $this->userId === null;
    }

    public function getIpLock(): string
    {
        return $this->ipLock;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function isPermanent(): bool
    {
        return $this->isPermanent;
    }

    public function needsUpdate(): bool
    {
        return $GLOBALS['EXEC_TIME'] > ($this->lastUpdated + self::SESSION_UPDATE_GRACE_PERIOD);
    }

    public static function createFromRecord(string $id, array $record, bool $markAsNew = false): self
    {
        $userSession = new self(
            $id,
            (int)($record['ses_userid'] ?? 0),
            (int)($record['ses_tstamp'] ?? 0),
            unserialize($record['ses_data'] ?? '', ['allowed_classes' => false]) ?: []
        );
        $userSession->ipLock = $record['ses_iplock'] ?? '';
        $userSession->isNew = $markAsNew;
        if (isset($record['ses_permanent'])) {
            $userSession->isPermanent = (bool)$record['ses_permanent'];
        }
        return $userSession;
    }

    public static function createNonFixated(string $identifier): self
    {
        $userSession = new self($identifier, 0, $GLOBALS['EXEC_TIME'], []);
        $userSession->isPermanent = false;
        $userSession->isNew = true;
        return $userSession;
    }

    public function toArray(): array
    {
        $data = [
            'ses_id' => $this->identifier,
            'ses_data' => serialize($this->data),
            'ses_userid' => (int)$this->userId,
            'ses_iplock' => $this->ipLock,
            'ses_tstamp' => $this->lastUpdated,
        ];
        if ($this->isPermanent) {
            $data['ses_permanent'] = 1;
        }
        return $data;
    }
}
