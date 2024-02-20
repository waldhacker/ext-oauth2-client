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

namespace Waldhacker\Oauth2Client\Updates;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use Waldhacker\Oauth2Client\Database\Query\Restriction\Oauth2BeUserProviderConfigurationRestriction;

class RenameClientConfigsTableUpdateWizard20220122130120 implements UpgradeWizardInterface, ChattyInterface
{
    private const OAUTH2_BE_CONFIG_TABLE = 'tx_oauth2_beuser_provider_configuration';
    const OAUTH2_LEGACY_CONFIG_TABLE = 'tx_oauth2_client_configs';
    private ConnectionPool $connectionPool;
    private OutputInterface $output;

    public function __construct(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function getIdentifier(): string
    {
        return 'oauth2_client_RenameClientConfigsTableUpdateWizard20220122130120';
    }

    public function getTitle(): string
    {
        return 'Migrate OAuth2 table tx_oauth2_client_configs to tx_oauth2_beuser_provider_configuration';
    }

    public function getDescription(): string
    {
        return 'Migrate OAuth2 table tx_oauth2_client_configs to tx_oauth2_beuser_provider_configuration';
    }

    public function executeUpdate(): bool
    {
        $errorExists = false;
        $connection = $this->connectionPool->getConnectionForTable(self::OAUTH2_BE_CONFIG_TABLE);
        foreach ($this->findAllFromLegacyTable() as $row) {
            $connection->insert(self::OAUTH2_BE_CONFIG_TABLE, $row);
            $lastInsertId = (int)$connection->lastInsertId(self::OAUTH2_BE_CONFIG_TABLE);
            if ($lastInsertId === (int)$row['uid']) {
                $this->output->writeln(sprintf('Record with uid %s was migrated', $row['uid']));
                $deletions = $connection->delete(self::OAUTH2_LEGACY_CONFIG_TABLE, ['uid' => (int)$row['uid']]);
                if ($deletions !== 1) {
                    $this->output->writeln(sprintf('ERROR: Record with uid %s could not be deleted', $row['uid']));
                    $errorExists = true;
                }
            } else {
                $this->output->writeln(sprintf('ERROR: Record with uid %s could not be migrated', $row['uid']));
                $errorExists = true;
            }
        }
        $connection->delete(self::OAUTH2_LEGACY_CONFIG_TABLE, ['deleted' => 1]);

        return !$errorExists;
    }

    public function updateNecessary(): bool
    {
        return $this->tableExists(self::OAUTH2_LEGACY_CONFIG_TABLE) && $this->tableIsEmpty(self::OAUTH2_BE_CONFIG_TABLE);
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    private function findAllFromLegacyTable(): \Generator
    {
        $qb = $this->connectionPool->getQueryBuilderForTable(self::OAUTH2_LEGACY_CONFIG_TABLE);
        $qb->getRestrictions()->removeByType(Oauth2BeUserProviderConfigurationRestriction::class);

        $result = $qb
            ->select('uid', 'tstamp', 'crdate', 'cruser_id', 'parentid', 'provider', 'identifier')
            ->from(self::OAUTH2_LEGACY_CONFIG_TABLE)
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('deleted', $qb->createNamedParameter(0, \PDO::PARAM_INT))
                )
            )
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            if (is_array($row)) {
                yield $row;
            }
        }

        return;
    }

    private function tableExists(string $tableName): bool
    {
        return $this->connectionPool
            ->getConnectionForTable($tableName)
            ->getSchemaManager()
            ->tablesExist([$tableName]);
    }

    private function tableIsEmpty(string $tableName): bool
    {
        $qb = $this->connectionPool->getQueryBuilderForTable($tableName);
        $qb->getRestrictions()->removeByType(Oauth2BeUserProviderConfigurationRestriction::class);
        return (int)$qb->count('*')->from($tableName)->executeQuery()->fetchOne() === 0;
    }
}
