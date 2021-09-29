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

namespace Waldhacker\Oauth2Client\Repository;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use Waldhacker\Oauth2Client\Database\Query\Restriction\Oauth2ClientConfigBackendRestriction;
use Waldhacker\Oauth2Client\Service\LoginService;

/**
 * If you directly instantiate this class, make sure to call `setMode()` in right after to set the operation mode.
 * Alternatively use BackendUserRepository or FrontendUserRepository.
 */
class UserRepository
{
    public const BACKEND = 'BE';
    public const FRONTEND = 'FE';

    protected DataHandler $dataHandler;
    protected Context $context;
    protected ConnectionPool $connectionPool;
    protected string $applicationType = '';
    protected string $table = '';

    public function __construct(
        DataHandler $dataHandler,
        Context $context,
        ConnectionPool $connectionPool
    ) {
        $this->dataHandler = $dataHandler;
        $this->context = $context;
        $this->connectionPool = $connectionPool;
    }

    public function setLoginType(string $loginType): void
    {
        switch ($loginType) {
            case self::BACKEND:
                $this->applicationType = 'backend';
                $this->table = 'be_users';
                break;
            case self::FRONTEND:
                $this->applicationType = 'frontend';
                $this->table = 'fe_users';
                break;
            default:
                throw new \InvalidArgumentException('No such mode: ' . $loginType, 1632914797969);
        }
    }

    /**
     * Fetch user by provider and identifier
     * @see LoginService
     *
     * @param string $provider
     * @param string $identifier
     * @return array|null
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function getUserByIdentity(string $provider, string $identifier): ?array
    {
        $qb = $this->connectionPool->getQueryBuilderForTable($this->table);
        $qb->getRestrictions()->removeByType(Oauth2ClientConfigBackendRestriction::class);

        $result = $qb->select($this->table . '.*')
            ->from('tx_oauth2_client_configs', 'config')
            ->join('config', $this->table, $this->table, 'config.parentid=' . $this->table . '.uid AND config.parenttable=\'' . $this->table . '\'')
            ->where($qb->expr()->eq('identifier', $qb->createNamedParameter($identifier)))
            ->andWhere($qb->expr()->eq('provider', $qb->createNamedParameter($provider)))
            ->execute()
            ->fetchAllAssociative();

        return $result[0] ?? null;
    }

    public function getActiveProviders(): array
    {
        $qb = $this->connectionPool->getQueryBuilderForTable($this->table);
        $userid = (int)$this->context->getPropertyFromAspect($this->applicationType . '.user', 'id');
        $result = $qb->select('config.*')
            ->from('tx_oauth2_client_configs', 'config')
            ->join('config', $this->table, $this->table, 'config.parentid=' . $this->table . '.uid AND config.parenttable=\'' . $this->table . '\'')
            ->where($qb->expr()->eq($this->table . '.uid', $qb->createNamedParameter($userid, \PDO::PARAM_INT)))
            ->execute()
            ->fetchAllAssociative();
        $keys = array_column($result, 'provider');
        return (array)array_combine($keys, $result);
    }
}
