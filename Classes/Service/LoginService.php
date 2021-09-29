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
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Service\AbstractService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use Waldhacker\Oauth2Client\Events\UserLookupEvent;
use Waldhacker\Oauth2Client\Repository\UserRepository;

class LoginService extends AbstractService
{
    const PROVIDER_ID = '1616569531';
    private array $loginData = [];
    private array $authInfo = [];
    private Oauth2Service $oauth2Service;
    private UriBuilder $uriBuilder;
    private UserRepository $userRepository;
    private ?ResourceOwnerInterface $user = null;
    private Oauth2ProviderManager $oauth2ProviderManager;


    public function __construct(
        Oauth2Service $oauth2Service,
        UriBuilder $uriBuilder,
        UserRepository $userRepository,
        Oauth2ProviderManager $oauth2ProviderManager
    ) {
        $this->oauth2Service = $oauth2Service;
        $this->uriBuilder = $uriBuilder;
        $this->userRepository = $userRepository;
        $this->oauth2ProviderManager = $oauth2ProviderManager;
    }

    public function initAuth(string $subType, array $loginData, array $authInfo): void
    {
        $this->loginData = $loginData;
        $this->authInfo = $authInfo;
        $this->userRepository->setLoginType($this->authInfo['loginType']);
    }

    public function getUser(): ?array
    {
        if ($this->loginData['status'] !== 'login') {
            return null;
        }
        $providerId = empty(GeneralUtility::_GP('oauth2-provider'))
            ? ''
            : (string)GeneralUtility::_GP('oauth2-provider');

        if (
            empty($providerId)
            || !$this->oauth2ProviderManager->hasProvider($providerId)
        ) {
            return null;
        }

        // Configure callback url parameters
        $callbackParams = [
            'loginProvider' => self::PROVIDER_ID,
            'oauth2-provider' => $providerId,
            'login_status' => 'login',
            'commandLI' => 'attempt',
        ];

        // Modify callback url for frontend logins
        if ($this->authInfo['loginType'] === 'FE') {
            // Set logintype param for felogin controller
            $callbackParams['logintype'] = 'login';

            // Get current frontend url
            $pathInfo = parse_url(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
            if (!empty($pathInfo['scheme']) && !empty($pathInfo['host']) && !empty($pathInfo['path'])) {
                $baseUrl = $pathInfo['scheme'] . '://' . $pathInfo['host'] . $pathInfo['path'];
            } else {
                $baseUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
            }

            // Generate frontend url
            $callbackUrl = preg_replace('/\?.*/', '', $baseUrl) . '?' . http_build_query($callbackParams);
        } else {
            // Generate backend url
            $callbackUrl = (string)$this->uriBuilder->buildUriFromRoute('login', $callbackParams, UriBuilder::ABSOLUTE_URL);
        }


        if (empty(GeneralUtility::_GET('code'))) {
            $authUrl = $this->oauth2Service->getAuthorizationUrl($providerId, $callbackUrl);
            HttpUtility::redirect($authUrl);
        } elseif (!empty(GeneralUtility::_GET('state')) && !empty(GeneralUtility::_GET('code'))) {
            $code = GeneralUtility::_GET('code');
            $state = GeneralUtility::_GET('state');

            $this->user = $this->oauth2Service->getUser(
                $code,
                $state,
                $providerId,
                $callbackUrl
            );

            if ($this->user === null) {
                return null;
            }

            $userRecord = $this->userRepository->getUserByIdentity($providerId, (string)$this->user->getId());
            /** @var EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
            $userRecord = $eventDispatcher->dispatch(new UserLookupEvent($providerId, $this->user, $userRecord, $code, $state, $this->authInfo['loginType']))->getUserRecord();
            if ($userRecord === null) {
                unset($this->user);
            }
        }
        return $userRecord ?? null;
    }

    public function authUser(): int
    {
        if (GeneralUtility::_GP('loginProvider') === self::PROVIDER_ID &&
            GeneralUtility::_GP('oauth2-provider') &&
            GeneralUtility::_GP('code')
        ) {
            if ($this->user instanceof ResourceOwnerInterface) {
                return 200;
            }
            return -100;
        }
        return 100;
    }
}
