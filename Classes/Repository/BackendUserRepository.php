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

namespace Waldhacker\Oauth2Client\Repository;

use Doctrine\DBAL\FetchMode;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use Waldhacker\Oauth2Client\Backend\DataHandling\DataHandlerHook;
use Waldhacker\Oauth2Client\Database\Query\Restriction\Oauth2BeUserProviderConfigurationRestriction;

class BackendUserRepository
{
    private const OAUTH2_BE_CONFIG_TABLE = 'tx_oauth2_beuser_provider_configuration';
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
        if ($provider === DataHandlerHook::INVALID_TOKEN || $identifier === DataHandlerHook::INVALID_TOKEN) {
            return null;
        }
        $userWithEditRightsColumn = $GLOBALS['TCA'][self::OAUTH2_BE_CONFIG_TABLE]['ctrl']['enablecolumns']['be_user'] ?? 'parentid';

        $qb = $this->connectionPool->getQueryBuilderForTable('be_users');
        $qb->getRestrictions()->removeByType(Oauth2BeUserProviderConfigurationRestriction::class);
        $result = $qb->select('be_users.*')
            ->from(self::OAUTH2_BE_CONFIG_TABLE, 'config')
            ->join('config', 'be_users', 'be_users', 'config.' . $userWithEditRightsColumn . '=be_users.uid')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('identifier', $qb->createNamedParameter($identifier, \PDO::PARAM_STR)),
                    $qb->expr()->eq('provider', $qb->createNamedParameter($provider, \PDO::PARAM_STR)),
                    $qb->expr()->neq('identifier', $qb->createNamedParameter(DataHandlerHook::INVALID_TOKEN, \PDO::PARAM_STR)),
                    $qb->expr()->neq('provider', $qb->createNamedParameter(DataHandlerHook::INVALID_TOKEN, \PDO::PARAM_STR))
                )
            )
            ->executeQuery();

        $result = $result->fetchAllAssociative();

        // @todo: log warning if more than one user matches
        // Do not login if more than one user matches!
        return empty($result) || empty($result[0]) || count($result) > 1 ? null : $result[0];
    }

    public function persistIdentityForUser(string $provider, string $identifier): void
    {
        if (empty($provider)) {
            throw new \InvalidArgumentException('"provider" must not be empty', 1642867950);
        }
        if (empty($identifier)) {
            throw new \InvalidArgumentException('"identifier" must not be empty', 1642867951);
        }

        $cmd = [];
        foreach ($this->getConfigurationsByIdentity($provider, $identifier) as $configuration) {
            $cmd[self::OAUTH2_BE_CONFIG_TABLE][(int)$configuration['uid']]['delete'] = 1;
        }

        $userid = (int)$this->context->getPropertyFromAspect('backend.user', 'id');
        $data = [
            'be_users' => [
                $userid => [
                    'tx_oauth2_client_configs' => 'NEW12345',
                ],
            ],
            self::OAUTH2_BE_CONFIG_TABLE => [
                'NEW12345' => [
                    'identifier' => $identifier,
                    'provider' => $provider,
                ],
            ],
        ];

        // see SetupModuleController - fake admin to allow manipulating be_users as editor
        $backendUser = $this->getBackendUser();
        $savedUserAdminState = $backendUser->user['admin'] ?? false;
        $backendUser->user['admin'] = true;
        $this->dataHandler->start($data, $cmd, $backendUser);
        $this->dataHandler->process_datamap();
        $this->dataHandler->process_cmdmap();
        $backendUser->user['admin'] = $savedUserAdminState;
    }

    public function getActiveProviders(): array
    {
        $userWithEditRightsColumn = $GLOBALS['TCA'][self::OAUTH2_BE_CONFIG_TABLE]['ctrl']['enablecolumns']['be_user'] ?? 'parentid';
        $userid = (int)$this->context->getPropertyFromAspect('backend.user', 'id');

        $qb = $this->connectionPool->getQueryBuilderForTable('be_users');
        $result = $qb->select('config.*')
            ->from(self::OAUTH2_BE_CONFIG_TABLE, 'config')
            ->join('config', 'be_users', 'be_users', 'config.' . $userWithEditRightsColumn . '=be_users.uid')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('be_users.uid', $qb->createNamedParameter($userid, \PDO::PARAM_INT)),
                    $qb->expr()->neq('config.identifier', $qb->createNamedParameter(DataHandlerHook::INVALID_TOKEN, \PDO::PARAM_STR)),
                    $qb->expr()->neq('config.provider', $qb->createNamedParameter(DataHandlerHook::INVALID_TOKEN, \PDO::PARAM_STR))
                )
            )
            ->executeQuery();

        $result = $result->fetchAllAssociative();

        $keys = array_column($result, 'provider');
        return (array)array_combine($keys, $result);
    }

    private function getConfigurationsByIdentity(string $provider, string $identifier): array
    {
        $userWithEditRightsColumn = $GLOBALS['TCA'][self::OAUTH2_BE_CONFIG_TABLE]['ctrl']['enablecolumns']['be_user'] ?? 'parentid';
        $userid = (int)$this->context->getPropertyFromAspect('backend.user', 'id');

        $qb = $this->connectionPool->getQueryBuilderForTable(self::OAUTH2_BE_CONFIG_TABLE);
        $qb->getRestrictions()->removeByType(Oauth2BeUserProviderConfigurationRestriction::class);
        $result = $qb->select('*')
            ->from(self::OAUTH2_BE_CONFIG_TABLE)
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('identifier', $qb->createNamedParameter($identifier, \PDO::PARAM_STR)),
                    $qb->expr()->eq('provider', $qb->createNamedParameter($provider, \PDO::PARAM_STR)),
                    $qb->expr()->neq('identifier', $qb->createNamedParameter(DataHandlerHook::INVALID_TOKEN, \PDO::PARAM_STR)),
                    $qb->expr()->neq('provider', $qb->createNamedParameter(DataHandlerHook::INVALID_TOKEN, \PDO::PARAM_STR)),
                    $qb->expr()->eq($userWithEditRightsColumn, $qb->createNamedParameter($userid, \PDO::PARAM_INT))
                )
            )
            ->executeQuery();

        $result = $result->fetchAllAssociative();

        return $result;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
