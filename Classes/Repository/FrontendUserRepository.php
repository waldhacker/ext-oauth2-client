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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use Waldhacker\Oauth2Client\Backend\DataHandling\DataHandlerHook;
use Waldhacker\Oauth2Client\Database\Query\Restriction\Oauth2FeUserProviderConfigurationRestriction;

class FrontendUserRepository
{
    private const OAUTH2_FE_CONFIG_TABLE = 'tx_oauth2_feuser_provider_configuration';
    private Context $context;
    private ConnectionPool $connectionPool;

    public function __construct(
        Context $context,
        ConnectionPool $connectionPool
    ) {
        $this->context = $context;
        $this->connectionPool = $connectionPool;
    }

    public function getUserByIdentity(string $provider, string $identifier, int $storagePid): ?array
    {
        if ($provider === DataHandlerHook::INVALID_TOKEN || $identifier === DataHandlerHook::INVALID_TOKEN) {
            return null;
        }
        $userWithEditRightsColumn = $GLOBALS['TCA'][self::OAUTH2_FE_CONFIG_TABLE]['ctrl']['enablecolumns']['fe_user'] ?? 'parentid';

        $qb = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $qb->getRestrictions()->removeByType(Oauth2FeUserProviderConfigurationRestriction::class);
        $result = $qb->select('fe_users.*')
            ->from(self::OAUTH2_FE_CONFIG_TABLE, 'config')
            ->join('config', 'fe_users', 'fe_users', 'config.' . $userWithEditRightsColumn . '=fe_users.uid')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('identifier', $qb->createNamedParameter($identifier, \PDO::PARAM_STR)),
                    $qb->expr()->eq('provider', $qb->createNamedParameter($provider, \PDO::PARAM_STR)),
                    $qb->expr()->neq('identifier', $qb->createNamedParameter(DataHandlerHook::INVALID_TOKEN, \PDO::PARAM_STR)),
                    $qb->expr()->neq('provider', $qb->createNamedParameter(DataHandlerHook::INVALID_TOKEN, \PDO::PARAM_STR)),
                    $qb->expr()->eq('fe_users.pid', $qb->createNamedParameter($storagePid, \PDO::PARAM_INT))
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
            throw new \InvalidArgumentException('"provider" must not be empty', 1642867960);
        }
        if (empty($identifier)) {
            throw new \InvalidArgumentException('"identifier" must not be empty', 1642867961);
        }

        $now = new \DateTime();
        $userWithEditRightsColumn = $GLOBALS['TCA'][self::OAUTH2_FE_CONFIG_TABLE]['ctrl']['enablecolumns']['fe_user'] ?? 'parentid';
        $userid = (int)$this->context->getPropertyFromAspect('frontend.user', 'id');

        $activeConfigurationUids = array_map('intval', array_column($this->getConfigurationsByIdentity($provider, $identifier), 'uid'));
        if (!empty($activeConfigurationUids)) {
            $qb = $this->connectionPool->getQueryBuilderForTable(self::OAUTH2_FE_CONFIG_TABLE);
            $qb->delete(self::OAUTH2_FE_CONFIG_TABLE)
                ->where(
                    $qb->expr()->and(
                        $qb->expr()->eq($userWithEditRightsColumn, $qb->createNamedParameter($userid, \PDO::PARAM_INT)),
                        $qb->expr()->in('uid', $qb->createNamedParameter($activeConfigurationUids, Connection::PARAM_INT_ARRAY))
                    )
                )
                ->executeStatement();
        }

        $qb = $this->connectionPool->getQueryBuilderForTable(self::OAUTH2_FE_CONFIG_TABLE);
        $qb->insert(self::OAUTH2_FE_CONFIG_TABLE)
            ->setValue('pid', 0)
            ->setValue('crdate', $now->format('U'))
            ->setValue('tstamp', $now->format('U'))
            ->setValue('cruser_id', $userid)
            ->setValue('parentid', $userid)
            ->setValue('provider', $provider)
            ->setValue('identifier', $identifier)
            ->executeStatement();

        $activeProviders = $this->getActiveProviders();
        $qb = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $qb->update('fe_users')
            ->set('tx_oauth2_client_configs', count($activeProviders))
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('uid', $qb->createNamedParameter($userid, \PDO::PARAM_INT))
                )
            )
            ->executeQuery();
    }

    public function getActiveProviders(): array
    {
        $userWithEditRightsColumn = $GLOBALS['TCA'][self::OAUTH2_FE_CONFIG_TABLE]['ctrl']['enablecolumns']['fe_user'] ?? 'parentid';
        $userid = (int)$this->context->getPropertyFromAspect('frontend.user', 'id');

        $qb = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $result = $qb->select('config.*')
            ->from(self::OAUTH2_FE_CONFIG_TABLE, 'config')
            ->join('config', 'fe_users', 'fe_users', 'config.' . $userWithEditRightsColumn . '=fe_users.uid')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('fe_users.uid', $qb->createNamedParameter($userid, \PDO::PARAM_INT)),
                    $qb->expr()->neq('config.identifier', $qb->createNamedParameter(DataHandlerHook::INVALID_TOKEN, \PDO::PARAM_STR)),
                    $qb->expr()->neq('config.provider', $qb->createNamedParameter(DataHandlerHook::INVALID_TOKEN, \PDO::PARAM_STR))
                )
            )
            ->executeQuery();

        $result = $result->fetchAllAssociative();

        $keys = array_column($result, 'provider');
        return (array)array_combine($keys, $result);
    }

    public function deactivateProviderByUid(int $providerUid): void
    {
        $activeProviders = $this->getActiveProviders();
        if (!in_array($providerUid, array_map('intval', array_column($activeProviders, 'uid')), true)) {
            return;
        }

        $userWithEditRightsColumn = $GLOBALS['TCA'][self::OAUTH2_FE_CONFIG_TABLE]['ctrl']['enablecolumns']['fe_user'] ?? 'parentid';
        $userid = (int)$this->context->getPropertyFromAspect('frontend.user', 'id');

        $qb = $this->connectionPool->getQueryBuilderForTable(self::OAUTH2_FE_CONFIG_TABLE);
        $qb->delete(self::OAUTH2_FE_CONFIG_TABLE)
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq($userWithEditRightsColumn, $qb->createNamedParameter($userid, \PDO::PARAM_INT)),
                    $qb->expr()->eq('uid', $qb->createNamedParameter($providerUid, \PDO::PARAM_INT))
                )
            )
            ->executeStatement();

        $qb = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $qb->update('fe_users')
            ->set('tx_oauth2_client_configs', count($activeProviders) - 1)
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('uid', $qb->createNamedParameter($userid, \PDO::PARAM_INT))
                )
            )
            ->executeStatement();
    }

    private function getConfigurationsByIdentity(string $provider, string $identifier): array
    {
        $userWithEditRightsColumn = $GLOBALS['TCA'][self::OAUTH2_FE_CONFIG_TABLE]['ctrl']['enablecolumns']['fe_user'] ?? 'parentid';
        $userid = (int)$this->context->getPropertyFromAspect('frontend.user', 'id');

        $qb = $this->connectionPool->getQueryBuilderForTable(self::OAUTH2_FE_CONFIG_TABLE);
        $qb->getRestrictions()->removeByType(Oauth2FeUserProviderConfigurationRestriction::class);
        $result = $qb->select('*')
            ->from(self::OAUTH2_FE_CONFIG_TABLE)
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
}
