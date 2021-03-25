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

class BackendUserRepository
{
    private DataHandler $dataHandler;
    private Context $context;
    private ConnectionPool $connectionPool;

    public function __construct(
        DataHandler $dataHandler,
        Context $context,
        ConnectionPool $connectionPool
    ) {
        $this->dataHandler = $dataHandler;
        $this->context = $context;
        $this->connectionPool = $connectionPool;
    }

    public function getUserByIdentity(string $provider, string $identifier): ?array
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('be_users');
        $result = $qb->select('be_users.*')
            ->from('tx_oauth2_client_configs', 'config')
            ->join('config', 'be_users', 'be_users', 'config.parentid=be_users.uid')
            ->where($qb->expr()->eq('identifier', $qb->createNamedParameter($identifier)))
            ->andWhere($qb->expr()->eq('provider', $qb->createNamedParameter($provider)))
            ->execute()
            ->fetchAllAssociative();
        return $result[0] ?? null;
    }

    public function persistIdentityForUser(string $provider, string $identifier): void
    {
        $userid = (int)$this->context->getPropertyFromAspect('backend.user', 'id');
        $data =
            [
                'be_users' => [
                    $userid => [
                        'tx_oauth2_client_configs' => 'NEW12345',
                    ],
                ],
                'tx_oauth2_client_configs' => [
                    'NEW12345' => [
                        'identifier' => $identifier,
                        'provider' => $provider,
                        'pid' => 0,
                    ],
                ],
            ];
        $this->dataHandler->start($data, []);
        $this->dataHandler->process_datamap();
    }

    public function getActiveProviders(): array
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('be_users');
        $userid = (int)$this->context->getPropertyFromAspect('backend.user', 'id');
        $result = $qb->select('config.*')
            ->from('tx_oauth2_client_configs', 'config')
            ->join('config', 'be_users', 'be_users', 'config.parentid=be_users.uid')
            ->where($qb->expr()->eq('be_users.uid', $qb->createNamedParameter($userid, \PDO::PARAM_INT)))
            ->execute()
            ->fetchAllAssociative();
        $keys = array_column($result, 'provider');
        return (array)array_combine($keys, $result);
    }
}
