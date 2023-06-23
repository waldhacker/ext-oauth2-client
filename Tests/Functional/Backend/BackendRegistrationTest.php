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

namespace Waldhacker\Oauth2Client\Tests\Functional\Backend;

use TYPO3\CMS\Core\Http\Uri;
use Waldhacker\Oauth2Client\Tests\Functional\Fixtures\InvalidAccessTokenResponseFromGitLabHttpMock;
use Waldhacker\Oauth2Client\Tests\Functional\Fixtures\SuccessfulGetUserFromGitLabHttpMock;
use Waldhacker\Oauth2Client\Tests\Functional\Framework\FormHandling\DataExtractor;
use Waldhacker\Oauth2Client\Tests\Functional\Framework\FormHandling\DataPusher;
use Waldhacker\Oauth2Client\Tests\Functional\Framework\FunctionalTestCase;

class BackendRegistrationTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function assertThatABackendUserIsAbleToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackend(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $backendUserUid = 1;
        // Login into backend
        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'admin', 'password');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersTestBackendModule($responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'no oauth2 be cookies are sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'no oauth2 fe cookies are sent to the client');

        $registerVerifyForm = (new DataPusher(new DataExtractor($responseData['pageMarkup']), '//*[@id="oauth2test-register-verify-form"]'));

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click "register with gitlab2-be"
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData('oauth2test-register-gitlab2-be', $responseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2RegistrationUri, $responseData['cookieData']), false);
        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertEquals('gitlab2', $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals(
            'http://localhost/typo3/oauth2/callback/handle?oauth2-provider=gitlab2-be&action=callback',
            $authorizationQuery['redirect_uri'] ?? null,
            'assert: the correct callback uri is sent'
        );
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(1, $oauth2BackendSessionData, 'assert: 1 oauth2 backend session was created');
        self::assertEquals(0, (int)$oauth2BackendSessionData[0]['ses_userid'], 'assert: the backend session is not connected to a user');
        self::assertEquals(['oauth2-state' => $requestedState], $oauth2BackendSessionData[0]['ses_data'], 'assert: the backend session contains the oauth2 state, which is also contained in the authorization request uri');

        // At this point we can't test the things that would normally be
        // done by the JavaScript popup in the backend.
        // We have to skip:
        //   => JavaScript popup opens
        //     => redirect to the remote instance
        //     => redirect to the callback
        //     => callback JavaScript fills the hidden verify form in the backend module
        //     => callback JavaScript submit the hidden verify form in the backend module
        //   => JS popup close)
        // We could continue testing at the point where the callback JavaScript
        // submit the hidden verify form in the backend module
        $registerVerifyForm
            ->with('oauth2-provider', 'gitlab2-be')
            ->with('oauth2-state', $requestedState)
            ->with('oauth2-code', 'some-remote-api-access-code');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part
        $responseData = $this->fetchBackendPageContens(
            $registerVerifyForm->toPostRequest($this->buildGetRequest(null, $responseData['cookieData'])),
            false,
            $this->buildRequestContext(['X_TYPO3_TESTING_FRAMEWORK' => ['HTTP' => ['mocks' => [
                // successful Oauth2Service::getUser() request
                'className' => SuccessfulGetUserFromGitLabHttpMock::class,
                'options' => [
                    'remoteUser' => [
                        'id' => 'user1-gitlab2-be-remote-identity',
                        'username' => 'user1-gitlab2-be',
                        'name' => 'user1 gitlab2-be',
                        'email' => 'user1-gitlab2-be@waldhacker.dev',
                    ],
                ],
            ]]]])
        );

        $backendUserSessionData = $this->getBackendSessionDataByUser($backendUserUid);
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();
        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        $redirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($redirectUri->getQuery(), $redirectQuery);

        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the backend oauth2 cookies is deleted');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertCount(0, $frontendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for frontend users');
        self::assertCount(1, $backendUserOauth2ProviderConfigurations, 'assert: 1 oauth2 provider configuration was created for backend users');
        self::assertEquals(0, (int)$backendUserOauth2ProviderConfigurations[0]['pid'], 'assert: created backend user oauth2 provider configuration is valid');
        self::assertEquals($backendUserUid, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: created backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: created backend user oauth2 provider configuration is valid');
        self::assertEquals('user1-gitlab2-be-remote-identity', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: created backend user oauth2 provider configuration is valid');
        self::assertEquals(
            '{"severity":0,"title":"Success","message":"Provider configuration successfully added.","storeInSession":true}',
            $backendUserSessionData[0]['ses_data']['core.template.flashMessages'][0] ?? null,
            'assert: flash message was created'
        );
        self::assertEquals('/typo3/oauth2/manage/providers', $redirectUri->getPath(), 'assert: we are redirected to the OAuth2 provider management module');
        self::assertEquals(
            'OAuth2: Done. Redirection to original requested location',
            $responseData['response']->getReasonPhrase(),
            'assert: response redirect reason is set'
        );
        self::assertEquals(302, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');
    }

    public function assertThatABackendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToNotBeUsedWithinTheBackendDataProvider(): \Generator
    {
        yield 'register with a frontend provider (gitlab4-fe)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab4-fe',
        ];

        yield 'register with a not configured provider (notconfigured)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-notconfigured',
        ];

        yield 'register with a not configured provider (_invalid_)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-_invalid_',
        ];
    }

    /**
     * @test
     * @dataProvider assertThatABackendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToNotBeUsedWithinTheBackendDataProvider
     */
    public function assertThatABackendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToNotBeUsedWithinTheBackend(string $oauth2RegistrationUriId): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        // Login into backend
        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'admin', 'password');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersTestBackendModule($responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookies are sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookies are sent to the client');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2RegistrationUriId, $responseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2RegistrationUri, $responseData['cookieData']), false);

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();
        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();

        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the backend oauth2 cookies is deleted');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertCount(0, $frontendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for frontend users');
        self::assertCount(0, $backendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for backend users');
        self::assertEquals(401, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');
    }

    public function assertThatABackendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendIfInvalidaDataIsSumbittedDataProvider(): \Generator
    {
        yield 'all empty' => [
            'formData' => [
                'oauth2-provider' => '',
                'oauth2-state' => '',
                'oauth2-code' => '',
            ],
            'clearSession' => false,
        ];

        yield 'empty provider' => [
            'formData' => [
                'oauth2-provider' => '',
                'oauth2-state' => '_state',
                'oauth2-code' => 'some-remote-api-access-code',
            ],
            'clearSession' => false,
        ];

        yield 'empty state' => [
            'formData' => [
                'oauth2-provider' => 'gitlab2-be',
                'oauth2-state' => '',
                'oauth2-code' => 'some-remote-api-access-code',
            ],
            'clearSession' => false,
        ];

        yield 'empty code' => [
            'formData' => [
                'oauth2-provider' => 'gitlab2-be',
                'oauth2-state' => '_state',
                'oauth2-code' => '',
            ],
            'clearSession' => false,
        ];

        yield 'unknown provider' => [
            'formData' => [
                'oauth2-provider' => 'notconfigured',
                'oauth2-state' => '_state',
                'oauth2-code' => 'some-remote-api-access-code',
            ],
            'clearSession' => false,
        ];

        yield 'invalid code' => [
            'formData' => [
                'oauth2-provider' => 'gitlab2-be',
                'oauth2-state' => '_state',
                'oauth2-code' => '_invalid',
            ],
            'clearSession' => false,
        ];

        yield 'invalid session data' => [
            'formData' => [
                'oauth2-provider' => 'gitlab2-be',
                'oauth2-state' => '_state',
                'oauth2-code' => 'some-remote-api-access-code',
            ],
            'clearSession' => true,
        ];
    }

    /**
     * @test
     * @dataProvider assertThatABackendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendIfInvalidaDataIsSumbittedDataProvider
     */
    public function assertThatABackendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendIfInvalidaDataIsSumbitted(array $formData, bool $clearSession): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $backendUserUid = 1;
        // Login into backend
        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'admin', 'password');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersTestBackendModule($responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookies are sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookies are sent to the client');

        $registerVerifyForm = (new DataPusher(new DataExtractor($responseData['pageMarkup']), '//*[@id="oauth2test-register-verify-form"]'));

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click "register with gitlab2-be"
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData('oauth2test-register-gitlab2-be', $responseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2RegistrationUri, $responseData['cookieData']), false);
        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertEquals('gitlab2', $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals(
            'http://localhost/typo3/oauth2/callback/handle?oauth2-provider=gitlab2-be&action=callback',
            $authorizationQuery['redirect_uri'] ?? null,
            'assert: the correct callback uri is sent'
        );
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(1, $oauth2BackendSessionData, 'assert: 1 oauth2 backend session was created');
        self::assertEquals(0, (int)$oauth2BackendSessionData[0]['ses_userid'], 'assert: the backend session is not connected to a user');
        self::assertEquals(['oauth2-state' => $requestedState], $oauth2BackendSessionData[0]['ses_data'], 'assert: the backend session contains the oauth2 state, which is also contained in the authorization request uri');

        // At this point we can't test the things that would normally be
        // done by the JavaScript popup in the backend.
        // We have to skip:
        //   => JavaScript popup opens
        //     => redirect to the remote instance
        //     => redirect to the callback
        //     => callback JavaScript fills the hidden verify form in the backend module
        //     => callback JavaScript submit the hidden verify form in the backend module
        //   => JS popup close)
        // We could continue testing at the point where the callback JavaScript
        // submit the hidden verify form in the backend module
        $formData['oauth2-state'] = $formData['oauth2-state'] === '_state' ? $requestedState : $formData['oauth2-state'];
        $registerVerifyForm
            ->with('oauth2-provider', $formData['oauth2-provider'])
            ->with('oauth2-state', $formData['oauth2-state'])
            ->with('oauth2-code', $formData['oauth2-code']);

        if ($clearSession) {
            $this->removeOauth2BackendSessionData();
        }

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part.
        $responseData = $this->fetchBackendPageContens(
            $registerVerifyForm->toPostRequest($this->buildGetRequest(null, $responseData['cookieData'])),
            false,
            $this->buildRequestContext(['X_TYPO3_TESTING_FRAMEWORK' => ['HTTP' => ['mocks' => [
                'className' => $formData['oauth2-code'] === '_invalid' ? InvalidAccessTokenResponseFromGitLabHttpMock::class : SuccessfulGetUserFromGitLabHttpMock::class,
                'options' => [
                    'remoteUser' => [
                        'id' => 'user1-gitlab2-be-remote-identity',
                        'username' => 'user1-gitlab2-be',
                        'name' => 'user1 gitlab2-be',
                        'email' => 'user1-gitlab2-be@waldhacker.dev',
                    ],
                ],
            ]]]])
        );

        $backendUserSessionData = $this->getBackendSessionDataByUser($backendUserUid);
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();
        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        $redirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($redirectUri->getQuery(), $redirectQuery);

        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the backend oauth2 cookies is deleted');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertCount(0, $frontendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for frontend users');
        self::assertCount(0, $backendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for backend users');
        self::assertEquals(
            '{"severity":1,"title":"Error while adding provider.","message":"Please try again. If the error persists, please check the logs or contact your system administrator.","storeInSession":true}',
            $backendUserSessionData[0]['ses_data']['core.template.flashMessages'][0] ?? null,
            'assert: flash message was created'
        );
        self::assertEquals('/typo3/oauth2/manage/providers', $redirectUri->getPath(), 'assert: we are redirected to the OAuth2 provider management module');

        self::assertEquals(
            'OAuth2: Not logged in or invalid data',
            $responseData['response']->getReasonPhrase(),
            'assert: response redirect reason is set'
        );
        self::assertEquals(302, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');
    }

    public function assertThatABackendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendIfUserLoginIsExpiredBeforeRegisterActionDataProvider(): \Generator
    {
        yield 'session is expired' => [
            'expiredByCookie' => false,
        ];

        yield 'cookie is expired' => [
            'expiredByCookie' => true,
        ];
    }

    /**
     * @test
     * @dataProvider assertThatABackendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendIfUserLoginIsExpiredBeforeRegisterActionDataProvider
     */
    public function assertThatABackendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendIfUserLoginIsExpiredBeforeRegisterAction(bool $expiredByCookie): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        // Login into backend
        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'admin', 'password');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersTestBackendModule($responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'no oauth2 be cookies are sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'no oauth2 fe cookies are sent to the client');

        $registerVerifyForm = (new DataPusher(new DataExtractor($responseData['pageMarkup']), '//*[@id="oauth2test-register-verify-form"]'));

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click "register with gitlab2-be"
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData('oauth2test-register-gitlab2-be', $responseData);

        if ($expiredByCookie) {
            unset($responseData['cookieData']['be_typo_user']);
        } else {
            $this->resetSessionData();
        }

        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2RegistrationUri, $responseData['cookieData']), false);

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();
        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();

        self::assertArrayNotHasKey('be_typo_user', $responseData['cookieData'], 'assert: no backend user cookie exists');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertTrue($responseData['response']->hasHeader('location'), 'assert: a redirect is made');
        self::assertEquals(
            '/typo3/login',
            (new Uri($responseData['response']->getHeaderLine('location')))->getPath(),
            'assert: login redirect is made'
        );
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertCount(0, $frontendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for frontend users');
        self::assertCount(0, $backendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for backend users');
        self::assertStringNotContainsString('toolbar-item-avatar', $responseData['pageMarkup'], 'assert: we are logged in');
    }

    public function assertThatABackendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendIfUserLoginIsExpiredBeforeVerifyActionDataProvider(): \Generator
    {
        yield 'session is expired' => [
            'expiredByCookie' => false,
        ];

        yield 'cookie is expired' => [
            'expiredByCookie' => true,
        ];
    }

    /**
     * @test
     * @dataProvider assertThatABackendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendIfUserLoginIsExpiredBeforeVerifyActionDataProvider
     */
    public function assertThatABackendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendIfUserLoginIsExpiredBeforeVerifyAction(bool $expiredByCookie): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        // Login into backend
        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'admin', 'password');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersTestBackendModule($responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'no oauth2 be cookies are sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'no oauth2 fe cookies are sent to the client');

        $registerVerifyForm = (new DataPusher(new DataExtractor($responseData['pageMarkup']), '//*[@id="oauth2test-register-verify-form"]'));

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click "register with gitlab2-be"
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData('oauth2test-register-gitlab2-be', $responseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2RegistrationUri, $responseData['cookieData']), false);
        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertEquals('gitlab2', $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals(
            'http://localhost/typo3/oauth2/callback/handle?oauth2-provider=gitlab2-be&action=callback',
            $authorizationQuery['redirect_uri'] ?? null,
            'assert: the correct callback uri is sent'
        );
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(1, $oauth2BackendSessionData, 'assert: 1 oauth2 backend session was created');
        self::assertEquals(0, (int)$oauth2BackendSessionData[0]['ses_userid'], 'assert: the backend session is not connected to a user');
        self::assertEquals(['oauth2-state' => $requestedState], $oauth2BackendSessionData[0]['ses_data'], 'assert: the backend session contains the oauth2 state, which is also contained in the authorization request uri');

        // At this point we can't test the things that would normally be
        // done by the JavaScript popup in the backend.
        // We have to skip:
        //   => JavaScript popup opens
        //     => redirect to the remote instance
        //     => redirect to the callback
        //     => callback JavaScript fills the hidden verify form in the backend module
        //     => callback JavaScript submit the hidden verify form in the backend module
        //   => JS popup close)
        // We could continue testing at the point where the callback JavaScript
        // submit the hidden verify form in the backend module
        $registerVerifyForm
            ->with('oauth2-provider', 'gitlab2-be')
            ->with('oauth2-state', $requestedState)
            ->with('oauth2-code', 'some-remote-api-access-code');

        if ($expiredByCookie) {
            unset($responseData['cookieData']['be_typo_user']);
        } else {
            $this->resetSessionData();
        }

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part
        $responseData = $this->fetchBackendPageContens(
            $registerVerifyForm->toPostRequest($this->buildGetRequest(null, $responseData['cookieData'])),
            false,
            $this->buildRequestContext(['X_TYPO3_TESTING_FRAMEWORK' => ['HTTP' => ['mocks' => [
                // successful Oauth2Service::getUser() request
                'className' => SuccessfulGetUserFromGitLabHttpMock::class,
                'options' => [
                    'remoteUser' => [
                        'id' => 'user1-gitlab2-be-remote-identity',
                        'username' => 'user1-gitlab2-be',
                        'name' => 'user1 gitlab2-be',
                        'email' => 'user1-gitlab2-be@waldhacker.dev',
                    ],
                ],
            ]]]])
        );

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();
        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();

        self::assertArrayNotHasKey('be_typo_user', $responseData['cookieData'], 'assert: no backend user cookie exists');
        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 backend cookie still exists');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');

        if ($expiredByCookie) {
            self::assertCount(1, $oauth2BackendSessionData, 'assert: the oauth2 frontend session still exists');
        } else {
            self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 frontend session exists');
        }

        self::assertTrue($responseData['response']->hasHeader('location'), 'assert: a redirect is made');
        self::assertEquals(
            '/typo3/login',
            (new Uri($responseData['response']->getHeaderLine('location')))->getPath(),
            'assert: login redirect is made'
        );
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $frontendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for frontend users');
        self::assertCount(0, $backendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for backend users');
        self::assertStringNotContainsString('toolbar-item-avatar', $responseData['pageMarkup'], 'assert: we are logged in');
    }
}
