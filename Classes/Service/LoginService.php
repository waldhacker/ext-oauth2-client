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

namespace Waldhacker\Oauth2Client\Service;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Service\AbstractService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use Waldhacker\Oauth2Client\Repository\BackendUserRepository;

class LoginService extends AbstractService
{
    const PROVIDER_ID = '1616569531';
    private array $loginData = [];
    private Oauth2Service $oauth2Service;
    private UriBuilder $uriBuilder;
    private BackendUserRepository $backendUserRepository;
    private ?ResourceOwnerInterface $user = null;

    public function __construct(
        Oauth2Service $oauth2Service,
        UriBuilder $uriBuilder,
        BackendUserRepository $backendUserRepository
    ) {
        $this->oauth2Service = $oauth2Service;
        $this->uriBuilder = $uriBuilder;
        $this->backendUserRepository = $backendUserRepository;
    }

    public function initAuth(string $subType, array $loginData): void
    {
        $this->loginData = $loginData;
    }

    public function getUser(): ?array
    {
        if ($this->loginData['status'] !== 'login') {
            return null;
        }
        $providerId = GeneralUtility::_GP('oauth2-provider');
        if (empty($providerId)) {
            return null;
        }
        $callbackUrl = (string)$this->uriBuilder->buildUriFromRoute('login', [
            'loginProvider' => self::PROVIDER_ID,
            'oauth2-provider' => $providerId,
            'login_status' => 'login'
        ], UriBuilder::ABSOLUTE_URL);
        if (empty(GeneralUtility::_GET('code'))) {
            $authUrl = $this->oauth2Service->getAuthorizationUrl($providerId, $callbackUrl);
            HttpUtility::redirect($authUrl);
        } elseif (!empty(GeneralUtility::_GET('state')) && !empty(GeneralUtility::_GET('code'))) {
            $this->user = $this->oauth2Service->getUser(
                GeneralUtility::_GET('code'),
                GeneralUtility::_GET('state'),
                $providerId,
                $callbackUrl
            );
            if ($this->user === null) {
                return null;
            }
            $userRecord = $this->backendUserRepository->getUserByIdentity($providerId, (string)$this->user->getId());
        }
        return $userRecord ?? null;
    }

    public function authUser(): int
    {
        if ($this->user instanceof ResourceOwnerInterface) {
            return 200;
        }
        return 100;
    }
}
