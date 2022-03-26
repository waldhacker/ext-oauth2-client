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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Authentication\IpLocker;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserSessionManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const SESSION_ID_LENGTH = 32;
    protected const GARBAGE_COLLECTION_LIFETIME = 86400;
    protected const LIFETIME_OF_ANONYMOUS_SESSION_DATA = 86400;

    protected int $sessionLifetime;

    protected int $garbageCollectionForAnonymousSessions = self::LIFETIME_OF_ANONYMOUS_SESSION_DATA;
    protected SessionBackendInterface $sessionBackend;
    protected IpLocker $ipLocker;

    public function __construct(SessionBackendInterface $sessionBackend, int $sessionLifetime, IpLocker $ipLocker)
    {
        $this->sessionBackend = $sessionBackend;
        $this->sessionLifetime = $sessionLifetime;
        $this->ipLocker = $ipLocker;
    }

    protected function setGarbageCollectionTimeoutForAnonymousSessions(int $garbageCollectionForAnonymousSessions = 0): void
    {
        if ($garbageCollectionForAnonymousSessions > 0) {
            $this->garbageCollectionForAnonymousSessions = $garbageCollectionForAnonymousSessions;
        }
    }

    public function createFromRequestOrAnonymous(ServerRequestInterface $request, string $cookieName): UserSession
    {
        $sessionId = (string)($request->getCookieParams()[$cookieName] ?? '');
        return $this->getSessionFromSessionId($sessionId) ?? $this->createAnonymousSession();
    }

    public function createFromGlobalCookieOrAnonymous(string $cookieName): UserSession
    {
        $sessionId = isset($_COOKIE[$cookieName]) ? stripslashes((string)$_COOKIE[$cookieName]) : '';
        return $this->getSessionFromSessionId($sessionId) ?? $this->createAnonymousSession();
    }

    public function createAnonymousSession(): UserSession
    {
        $randomSessionId = $this->createSessionId();
        return UserSession::createNonFixated($randomSessionId);
    }

    public function createSessionFromStorage(string $sessionId): UserSession
    {
        if ($this->logger) {
            $this->logger->debug('Fetch session with identifier {session}', ['session' => sha1($sessionId)]);
        }
        $sessionRecord = $this->sessionBackend->get($sessionId);
        return UserSession::createFromRecord($sessionId, $sessionRecord);
    }

    public function hasExpired(UserSession $session): bool
    {
        return $this->sessionLifetime === 0 || $GLOBALS['EXEC_TIME'] > $session->getLastUpdated() + $this->sessionLifetime;
    }

    public function willExpire(UserSession $session, int $gracePeriod): bool
    {
        return $GLOBALS['EXEC_TIME'] >= ($session->getLastUpdated() + $this->sessionLifetime) - $gracePeriod;
    }

    public function fixateAnonymousSession(UserSession $session, bool $isPermanent = false): UserSession
    {
        $remoteAddress = GeneralUtility::getIndpEnv('REMOTE_ADDR');
        $sessionIpLock = $this->ipLocker->getSessionIpLock((is_string($remoteAddress) ? $remoteAddress : ''));
        $sessionRecord = $session->toArray();
        $sessionRecord['ses_iplock'] = $sessionIpLock;
        $sessionRecord['ses_userid'] = 0;
        if ($isPermanent) {
            $sessionRecord['ses_permanent'] = 1;
        }
        $updatedSessionRecord = $this->sessionBackend->set($session->getIdentifier(), $sessionRecord);
        return $this->recreateUserSession($session, $updatedSessionRecord);
    }

    public function elevateToFixatedUserSession(UserSession $session, int $userId, bool $isPermanent = false): UserSession
    {
        $sessionId = $session->getIdentifier();
        if ($this->logger) {
            $this->logger->debug('Create session ses_id = {session}', ['session' => sha1($sessionId)]);
        }
        $this->sessionBackend->remove($sessionId);
        $remoteAddress = GeneralUtility::getIndpEnv('REMOTE_ADDR');
        $sessionIpLock = $this->ipLocker->getSessionIpLock((is_string($remoteAddress) ? $remoteAddress : ''));
        $sessionRecord = [
            'ses_iplock' => $sessionIpLock,
            'ses_userid' => $userId,
            'ses_tstamp' => $GLOBALS['EXEC_TIME'],
            'ses_data' => '',
        ];
        if ($isPermanent) {
            $sessionRecord['ses_permanent'] = 1;
        }
        $sessionRecord = $this->sessionBackend->set($sessionId, $sessionRecord);
        return UserSession::createFromRecord($sessionId, $sessionRecord, true);
    }

    public function regenerateSession(
        string $sessionId,
        array $existingSessionRecord = [],
        bool $anonymous = false
    ): UserSession {
        if (empty($existingSessionRecord)) {
            $existingSessionRecord = $this->sessionBackend->get($sessionId);
        }
        if ($anonymous) {
            $existingSessionRecord['ses_userid'] = 0;
        }

        $newSessionId = $this->createSessionId();
        $this->sessionBackend->set($newSessionId, $existingSessionRecord);
        $this->sessionBackend->remove($sessionId);
        return UserSession::createFromRecord($newSessionId, $existingSessionRecord, true);
    }

    public function updateSessionTimestamp(UserSession $session): UserSession
    {
        if ($session->needsUpdate()) {
            $this->sessionBackend->update($session->getIdentifier(), []);
            $session = $this->recreateUserSession($session);
        }
        return $session;
    }

    public function isSessionPersisted(UserSession $session): bool
    {
        return $this->getSessionFromSessionId($session->getIdentifier()) !== null;
    }

    public function removeSession(UserSession $session): void
    {
        $this->sessionBackend->remove($session->getIdentifier());
    }

    public function updateSession(UserSession $session): UserSession
    {
        $sessionRecord = $this->sessionBackend->update($session->getIdentifier(), $session->toArray());
        return $this->recreateUserSession($session, $sessionRecord);
    }

    public function collectGarbage(int $garbageCollectionProbability = 1): void
    {
        if (random_int(0, mt_getrandmax()) % 100 <= $garbageCollectionProbability) {
            $this->sessionBackend->collectGarbage(
                $this->sessionLifetime > 0 ? $this->sessionLifetime : self::GARBAGE_COLLECTION_LIFETIME,
                $this->garbageCollectionForAnonymousSessions
            );
        }
    }

    protected function createSessionId(): string
    {
        return GeneralUtility::makeInstance(Random::class)->generateRandomHexString(self::SESSION_ID_LENGTH);
    }

    protected function getSessionFromSessionId(string $id): ?UserSession
    {
        if ($id === '') {
            return null;
        }
        try {
            $sessionRecord = $this->sessionBackend->get($id);
            if ($sessionRecord === []) {
                return null;
            }

            $remoteAddress = GeneralUtility::getIndpEnv('REMOTE_ADDR');
            if ($this->ipLocker->validateRemoteAddressAgainstSessionIpLock(
                (is_string($remoteAddress) ? $remoteAddress : ''),
                $sessionRecord['ses_iplock']
            )) {
                return UserSession::createFromRecord($id, $sessionRecord);
            }
        } catch (SessionNotFoundException $e) {
            return null;
        }

        return null;
    }

    public static function create(string $loginType, int $sessionLifetime = null, SessionManager $sessionManager = null, IpLocker $ipLocker = null): self
    {
        $sessionManager = $sessionManager ?? GeneralUtility::makeInstance(SessionManager::class);
        $ipLocker = $ipLocker ?? GeneralUtility::makeInstance(
            IpLocker::class,
            $GLOBALS['TYPO3_CONF_VARS'][$loginType]['lockIP'],
            $GLOBALS['TYPO3_CONF_VARS'][$loginType]['lockIPv6']
        );
        $lifetime = (int)($GLOBALS['TYPO3_CONF_VARS'][$loginType]['lifetime'] ?? 0);
        $sessionLifetime = $sessionLifetime ?? (int)$GLOBALS['TYPO3_CONF_VARS'][$loginType]['sessionTimeout'];
        if ($sessionLifetime > 0 && $sessionLifetime < $lifetime && $lifetime > 0) {
            $sessionLifetime = $lifetime;
        }
        $object = GeneralUtility::makeInstance(
            self::class,
            $sessionManager->getSessionBackend($loginType),
            $sessionLifetime,
            $ipLocker
        );
        if ($loginType === 'FE') {
            $object->setGarbageCollectionTimeoutForAnonymousSessions((int)($GLOBALS['TYPO3_CONF_VARS']['FE']['sessionDataLifetime'] ?? 0));
        }
        return $object;
    }

    protected function recreateUserSession(UserSession $session, array $sessionRecord = null): UserSession
    {
        return UserSession::createFromRecord(
            $session->getIdentifier(),
            $sessionRecord ?? $this->sessionBackend->get($session->getIdentifier()),
            $session->isNew()
        );
    }
}
