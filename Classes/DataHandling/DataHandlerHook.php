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

namespace Waldhacker\Oauth2Client\DataHandling;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\MathUtility;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;

class DataHandlerHook
{
    private Oauth2ProviderManager $oauth2ProviderManager;

    public function __construct(Oauth2ProviderManager $oauth2ProviderManager)
    {
        $this->oauth2ProviderManager = $oauth2ProviderManager;
    }

    /**
     * Only allow write to provider and identifier for new tx_oauth2_client_configs records.
     * @see Waldhacker\Oauth2Client\Repository\BackendUserRepository::persistIdentityForUser
     * @see Waldhacker\Oauth2Client\Database\Query\Restriction\Oauth2ClientConfigBackendRestriction
     *
     * @param array $incomingFieldArray
     * @param string $table
     * @param string|int $id
     * @param DataHandler $dataHandler
     */
    public function processDatamap_preProcessFieldArray(array &$incomingFieldArray, string $table, $id, DataHandler $dataHandler): void
    {
        if ($table !== 'tx_oauth2_client_configs') {
            return;
        }

        if ($id === 'NEW12345') {
            $incomingFieldArray = array_intersect_key($incomingFieldArray, array_flip(['provider', 'identifier']));
            if (!$this->oauth2ProviderManager->hasProvider($incomingFieldArray['provider'])) {
                $incomingFieldArray = [];
            }

            $incomingFieldArray['pid'] = 0;
        } else {
            $incomingFieldArray = [];
        }
    }

    /**
     * Only allow the 'delete' command for tx_oauth2_client_configs records
     * @see Resources/Private/Templates/Backend/Register.html -> deactivate button
     * @see Waldhacker\Oauth2Client\Database\Query\Restriction\Oauth2ClientConfigBackendRestriction
     *
     * @param string $command
     * @param string $table
     * @param string|int $id
     * @param mixed $value
     * @param bool $commandIsProcessed
     * @param DataHandler $dataHandler
     * @param bool $pasteUpdate
     */
    public function processCmdmap(string $command, string $table, $id, $value, bool &$commandIsProcessed, DataHandler $dataHandler, bool $pasteUpdate): void
    {
        if ($table !== 'tx_oauth2_client_configs') {
            return;
        }

        if (
            !MathUtility::canBeInterpretedAsInteger($id)
            || $command !== 'delete'
        ) {
            $commandIsProcessed = true;
            return;
        }
    }
}
