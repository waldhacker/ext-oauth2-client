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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;

class BackendUserRepository extends UserRepository
{
    public function __construct(DataHandler $dataHandler, Context $context, ConnectionPool $connectionPool)
    {
        parent::__construct($dataHandler, $context, $connectionPool);
        $this->setLoginType(self::BACKEND);
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
                    ],
                ],
            ];
        // see SetupModuleController - fake admin to allow manipulating be_users as editor
        $backendUser = $this->getBackendUser();
        $savedUserAdminState = $backendUser->user['admin'];
        $backendUser->user['admin'] = true;
        $this->dataHandler->start($data, [], $backendUser);
        $this->dataHandler->process_datamap();
        $backendUser->user['admin'] = $savedUserAdminState;
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
