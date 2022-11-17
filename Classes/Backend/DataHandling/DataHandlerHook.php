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

namespace Waldhacker\Oauth2Client\Backend\DataHandling;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\DataHandlerCheckModifyAccessListHookInterface;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;

class DataHandlerHook implements DataHandlerCheckModifyAccessListHookInterface
{
    public const INVALID_TOKEN = '_invalid_';
    private const OAUTH2_FE_TABLE = 'tx_oauth2_feuser_provider_configuration';
    private const OAUTH2_BE_TABLE = 'tx_oauth2_beuser_provider_configuration';
    private const OAUTH2_CLIENT_CONFIG_FIELD = 'tx_oauth2_client_configs';
    private const FE_USERS_TABLE = 'fe_users';
    private Oauth2ProviderManager $oauth2ProviderManager;

    public function __construct(Oauth2ProviderManager $oauth2ProviderManager)
    {
        $this->oauth2ProviderManager = $oauth2ProviderManager;
    }

    /**
     * Restrict data handler operations on tx_oauth2_beuser_provider_configuration and tx_oauth2_feuser_provider_configuration records.
     *
     * @see \Waldhacker\Oauth2Client\Repository\BackendUserRepository::persistIdentityForUser
     * @see \Waldhacker\Oauth2Client\Database\Query\Restriction\Oauth2BeUserProviderConfigurationRestriction
     *
     * @param string|int $id
     */
    public function processDatamap_preProcessFieldArray(array &$incomingFieldArray, string $table, $id, DataHandler $dataHandler): void
    {
        $isNew = is_string($id) && !empty($id) && strncasecmp($id, 'NEW', 3) === 0;

        // Invalidate every attempt to create or modify frontend OAuth2 client configs via data handler.
        if ($table === self::FE_USERS_TABLE) {
            unset($incomingFieldArray[self::OAUTH2_CLIENT_CONFIG_FIELD]);
            return;
        }
        if ($table === self::OAUTH2_FE_TABLE) {
            $userWithEditRightsColumn = $GLOBALS['TCA'][self::OAUTH2_FE_TABLE]['ctrl']['enablecolumns']['fe_user'] ?? 'parentid';
            if ($isNew) {
                $incomingFieldArray = [
                    'pid' => 0,
                    $userWithEditRightsColumn => 0,
                    'provider' => self::INVALID_TOKEN,
                    'identifier' => self::INVALID_TOKEN,
                ];
            } else {
                $incomingFieldArray = [];
            }

            return;
        }

        if ($table === self::OAUTH2_BE_TABLE) {
            $userWithEditRightsColumn = $GLOBALS['TCA'][self::OAUTH2_BE_TABLE]['ctrl']['enablecolumns']['be_user'] ?? 'parentid';
            if ($isNew) {
                // Only allow to set the properties "provider" and "identifier" for new backend OAuth2 client configs via data handler.
                $incomingFieldArray = array_intersect_key($incomingFieldArray, array_flip(['provider', 'identifier']));
                if (!$this->oauth2ProviderManager->hasBackendProvider($incomingFieldArray['provider'] ?? '')) {
                    $incomingFieldArray = [
                        $userWithEditRightsColumn => 0,
                        'provider' => self::INVALID_TOKEN,
                        'identifier' => self::INVALID_TOKEN,
                    ];
                }

                $incomingFieldArray['pid'] = 0;
            } else {
                // Invalidate every attempt to modify backend OAuth2 client configs via data handler.
                $incomingFieldArray = [];
            }
        }
    }

    /**
     * Only allow the 'delete' command for tx_oauth2_beuser_provider_configuration
     * and tx_oauth2_feuser_provider_configuration records.
     *
     * @see Resources/Private/Templates/Backend/Register.html -> deactivate button
     * @see \Waldhacker\Oauth2Client\Backend\Form\RenderType\Oauth2ProvidersElement
     * @see \Waldhacker\Oauth2Client\Database\Query\Restriction\Oauth2BeUserProviderConfigurationRestriction
     *
     * @param string|int $id
     * @param mixed $value
     * @param bool|string $pasteUpdate
     */
    public function processCmdmap(string $command, string $table, $id, $value, bool &$commandIsProcessed, DataHandler $dataHandler, $pasteUpdate): void
    {
        if ($table === self::OAUTH2_BE_TABLE || $table === self::OAUTH2_FE_TABLE) {
            $commandIsProcessed = strtolower($command) !== 'delete';
        }
    }

    /**
     * @param bool $accessAllowed
     * @param string $table
     */
    public function checkModifyAccessList(&$accessAllowed, $table, DataHandler $parent): void
    {
        if ($table === self::OAUTH2_BE_TABLE) {
            $accessAllowed = true;
        }
    }
}
