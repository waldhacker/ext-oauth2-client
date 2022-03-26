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

namespace Waldhacker\Oauth2Client\Tests\Functional\Frontend;

use TYPO3\CMS\Core\Http\Uri;
use Waldhacker\Oauth2Client\Tests\Functional\Fixtures\InvalidAccessTokenResponseFromGitLabHttpMock;
use Waldhacker\Oauth2Client\Tests\Functional\Fixtures\SuccessfulGetUserFromGitLabHttpMock;
use Waldhacker\Oauth2Client\Tests\Functional\Framework\FunctionalTestCase;

class FrontendRegistrationTest extends FunctionalTestCase
{
    public function assertThatAFrontendUserIsAbleToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendDataProvider(): \Generator
    {
        yield 'site1 EN with gitlab3-both' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab3-both',
            'siteHost' => self::SITE1_HOST,
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/en',
            'expectedProvider' => 'gitlab3-both',
            'expectedRemoteInstanceHost' => 'gitlab3',
            'expectedRemoteClientId' => 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc',
            'remoteUserData' => [
                'id' => 'user1-gitlab3-both-remote-identity',
                'username' => 'user1-gitlab3-both',
                'name' => 'user1 gitlab3-both',
                'email' => 'user1-gitlab3-both@waldhacker.dev',
            ],
        ];

        yield 'site1 DE with gitlab1-fe' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab1-fe',
            'siteHost' => self::SITE1_HOST,
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/de',
            'expectedProvider' => 'gitlab1-fe',
            'expectedRemoteInstanceHost' => 'gitlab1',
            'expectedRemoteClientId' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'remoteUserData' => [
                'id' => 'user1-gitlab1-fe-remote-identity',
                'username' => 'user1-gitlab1-fe',
                'name' => 'user1 gitlab1-fe',
                'email' => 'user1-gitlab1-fe@waldhacker.dev',
            ],
        ];

        yield 'site2 EN with gitlab6-both' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab6-both',
            'siteHost' => self::SITE2_HOST,
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/en',
            'expectedProvider' => 'gitlab6-both',
            'expectedRemoteInstanceHost' => 'gitlab6',
            'expectedRemoteClientId' => 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff',
            'remoteUserData' => [
                'id' => 'user1-gitlab6-both-remote-identity',
                'username' => 'user1-gitlab6-both',
                'name' => 'user1 gitlab6-both',
                'email' => 'user1-gitlab6-both@waldhacker.dev',
            ],
        ];

        yield 'site2 DE with gitlab1-fe' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab1-fe',
            'siteHost' => self::SITE2_HOST,
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/de',
            'expectedProvider' => 'gitlab1-fe',
            'expectedRemoteInstanceHost' => 'gitlab1',
            'expectedRemoteClientId' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'remoteUserData' => [
                'id' => 'user1-gitlab1-fe-remote-identity',
                'username' => 'user1-gitlab1-fe',
                'name' => 'user1 gitlab1-fe',
                'email' => 'user1-gitlab1-fe@waldhacker.dev',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider assertThatAFrontendUserIsAbleToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendDataProvider
     */
    public function assertThatAFrontendUserIsAbleToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontend(
        string $oauth2RegistrationUriId,
        string $siteHost,
        string $siteBaseUri,
        string $languageSlug,
        string $expectedProvider,
        string $expectedRemoteInstanceHost,
        string $expectedRemoteClientId,
        array $remoteUserData
    ): void {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $frontendUserUid = 1000;
        // Login into frontend
        $responseData = $this->loginIntoFrontendWithUsernameAndPassword($siteBaseUri, $languageSlug, 'user1', 'password');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersFrontendPage($siteBaseUri, $languageSlug, $responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 frontend cookie is sent to the client');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click register link
        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2RegistrationUriId, $responseData);
        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest($siteBaseUri . $oauth2RegistrationUri, $responseData['cookieData']), false);
        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertEquals($expectedRemoteInstanceHost, $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals($expectedRemoteClientId, $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals($siteBaseUri . $languageSlug . '/_oauth2?oauth2-provider=' . $expectedProvider . '&tx_oauth2client%5Baction%5D=verify', $authorizationQuery['redirect_uri'] ?? null, 'assert: the correct callback uri is sent');
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(1, $oauth2FrontendSessionData, 'assert: 1 oauth2 frontend session was created');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertEquals(0, (int)$oauth2FrontendSessionData[0]['ses_userid'], 'assert: the frontend session is not connected to a user');
        self::assertEquals(
            [
                'oauth2-original-registration-request-data' => [
                    'protocolVersion' => '1.1',
                    'method' => 'GET',
                    'uri'=> $siteBaseUri . $languageSlug . '/manage-providers',
                    'headers' => [
                        'user-agent' => ['TYPO3 Functional Test Request'],
                        'host' => [$siteHost],
                    ],
                    'parsedBody' => []
                ],
                'oauth2-state' => $requestedState
            ],
            $oauth2FrontendSessionData[0]['ses_data'],
            'assert: the frontend session contains the oauth2 state, which is also contained in the authorization request uri and the original request data'
        );

        // Simulate the redirect from the remote instance back to the TYPO3
        $requestedCallbackUri = new Uri(urldecode($authorizationQuery['redirect_uri']));
        parse_str($requestedCallbackUri->getQuery(), $respondedCallbackQuery);
        $respondedCallbackQuery['state'] = $requestedState;
        $respondedCallbackQuery['code'] = 'some-remote-api-access-code';
        $respondedCallbackUri = (string)$requestedCallbackUri->withQuery(http_build_query($respondedCallbackQuery));

        // At this point we were redirected back from the remote instance to the TYPO3
        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part
        $responseData = $this->fetchFrontendPageContens(
            $this->buildGetRequest($respondedCallbackUri, $responseData['cookieData']),
            false,
            $this->buildRequestContext(['X_TYPO3_TESTING_FRAMEWORK' => ['HTTP' => ['mocks' => [
                // successful Oauth2Service::getUser() request
                'className' => SuccessfulGetUserFromGitLabHttpMock::class,
                'options' => [
                    'remoteUser' => $remoteUserData,
                ],
            ]]]])
        );

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();
        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        $redirectUri = new Uri($responseData['response']->getHeaderLine('location'));

        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the frontend oauth2 cookies is deleted');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertCount(1, $frontendUserOauth2ProviderConfigurations, 'assert: 1 oauth2 provider configuration was created for frontend users');
        self::assertCount(0, $backendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for backend users');
        self::assertEquals(0, (int)$frontendUserOauth2ProviderConfigurations[0]['pid'], 'assert: created frontend user oauth2 provider configuration is valid');
        self::assertEquals($frontendUserUid, (int)$frontendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: created frontend user oauth2 provider configuration is valid');
        self::assertEquals($expectedProvider, $frontendUserOauth2ProviderConfigurations[0]['provider'], 'assert: created frontend user oauth2 provider configuration is valid');
        self::assertEquals($remoteUserData['id'], $frontendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: created frontend user oauth2 provider configuration is valid');
        self::assertEquals($siteHost, $redirectUri->getHost(), 'assert: we are redirected to the right host');
        self::assertEquals($languageSlug . '/manage-providers', $redirectUri->getPath(), 'assert: we are redirected to the OAuth2 provider management module');
        self::assertEquals(
            'OAuth2: Done. Redirection to original requested location',
            $responseData['response']->getReasonPhrase(),
            'assert: response redirect reason is set'
        );
        self::assertEquals(302, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');
    }

    public function assertThatAFrontendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToNotBeUsedWithinTheFrontendOrSiteDataProvider(): \Generator
    {
        yield 'site1 EN - register a backend provider (gitlab2-be)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab2-be',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/en',
        ];

        yield 'site1 EN - register a frontend provider from site2 EN (gitlab6-both)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab6-both',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/en',
        ];

        yield 'site1 EN - register a frontend provider from site1 DE (gitlab4-fe)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab4-fe',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/en',
        ];

        yield 'site1 EN - register a frontend provider from site2 DE (gitlab7-fe)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab7-fe',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/en',
        ];

        yield 'site1 EN - register a no configured provider (notconfigured)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-notconfigured',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/en',
        ];

        yield 'site1 EN - register a no configured provider (_invalid_)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-_invalid_',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/en',
        ];

        yield 'site1 DE - register a backend provider (gitlab2-be)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab2-be',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/de',
        ];

        yield 'site1 DE - register a frontend provider from site2 DE (gitlab7-fe)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab7-fe',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/de',
        ];

        yield 'site1 DE - register a frontend provider from site1 EN (gitlab3-both)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab3-both',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/de',
        ];

        yield 'site1 DE - register a frontend provider from site2 EN (gitlab6-both)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab6-both',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/de',
        ];

        yield 'site1 DE - register a no configured provider (notconfigured)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-notconfigured',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/de',
        ];

        yield 'site1 DE - register a no configured provider (_invalid_)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-_invalid_',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/de',
        ];

        yield 'site2 EN - register a backend provider (gitlab2-be)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab2-be',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/en',
        ];

        yield 'site2 EN - register a frontend provider from site1 EN (gitlab3-both)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab3-both',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/en',
        ];

        yield 'site2 EN - register a frontend provider from site2 DE (gitlab1-fe)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab1-fe',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/en',
        ];

        yield 'site2 EN - register a frontend provider from site1 DE (gitlab8-fe)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab8-fe',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/en',
        ];

        yield 'site2 EN - register a no configured provider (notconfigured)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-notconfigured',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/en',
        ];

        yield 'site2 EN - register a no configured provider (_invalid_)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-_invalid_',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/en',
        ];

        yield 'site2 DE - register a backend provider (gitlab2-be)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab2-be',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/de',
        ];

        yield 'site2 DE - register a frontend provider from site1 DE (gitlab6-both)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab6-both',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/de',
        ];

        yield 'site2 DE - register a frontend provider from site2 EN (gitlab6-both)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab6-both',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/de',
        ];

        yield 'site2 DE - register a frontend provider from site1 EN (gitlab3-both)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-gitlab3-both',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/de',
        ];

        yield 'site2 DE - register a no configured provider (notconfigured)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-notconfigured',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/de',
        ];

        yield 'site2 DE - register a no configured provider (_invalid_)' => [
            'oauth2RegistrationUriId' => 'oauth2test-register-_invalid_',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/de',
        ];
    }

    /**
     * @test
     * @dataProvider assertThatAFrontendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToNotBeUsedWithinTheFrontendOrSiteDataProvider
     */
    public function assertThatAFrontendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToNotBeUsedWithinTheFrontendOrSite(
        string $oauth2RegistrationUriId,
        string $siteBaseUri,
        string $languageSlug
    ): void {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        // Login into frontend
        $responseData = $this->loginIntoFrontendWithUsernameAndPassword($siteBaseUri, $languageSlug, 'user1', 'password');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersFrontendPage($siteBaseUri, $languageSlug, $responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 frontend cookie is sent to the client');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click register link
        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2RegistrationUriId, $responseData);
        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest($siteBaseUri . $oauth2RegistrationUri, $responseData['cookieData']), false);

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();
        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();

        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the frontend oauth2 cookies is deleted');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertCount(0, $frontendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for frontend users');
        self::assertCount(0, $backendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for backend users');
        self::assertEquals(401, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');
    }

    public function assertThatAFrontendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendIfInvalidaDataIsSumbittedDataProvider(): \Generator
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
                'oauth2-provider' => 'gitlab3-both',
                'oauth2-state' => '',
                'oauth2-code' => 'some-remote-api-access-code',
            ],
            'clearSession' => false,
        ];

        yield 'empty code' => [
            'formData' => [
                'oauth2-provider' => 'gitlab3-both',
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
                'oauth2-provider' => 'gitlab3-both',
                'oauth2-state' => '_state',
                'oauth2-code' => '_invalid',
            ],
            'clearSession' => false,
        ];

        yield 'invalid session data' => [
            'formData' => [
                'oauth2-provider' => 'gitlab3-both',
                'oauth2-state' => '_state',
                'oauth2-code' => 'some-remote-api-access-code',
            ],
            'clearSession' => true,
        ];
    }

    /**
     * @test
     * @dataProvider assertThatAFrontendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendIfInvalidaDataIsSumbittedDataProvider
     */
    public function assertThatAFrontendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendIfInvalidaDataIsSumbitted(array $remoteRequestData, bool $clearSession): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $oauth2RegistrationUriId = 'oauth2test-register-gitlab3-both';
        $siteHost = self::SITE1_HOST;
        $siteBaseUri = self::SITE1_BASE_URI;
        $languageSlug = '/en';
        $expectedProvider = 'gitlab3-both';
        $expectedRemoteInstanceHost = 'gitlab3';
        $expectedRemoteClientId = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';
        $remoteUserData = [
            'id' => 'user1-gitlab3-both-remote-identity',
            'username' => 'user1-gitlab3-both',
            'name' => 'user1 gitlab3-both',
            'email' => 'user1-gitlab3-both@waldhacker.dev',
        ];

        $frontendUserUid = 1000;
        // Login into frontend
        $responseData = $this->loginIntoFrontendWithUsernameAndPassword($siteBaseUri, $languageSlug, 'user1', 'password');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersFrontendPage($siteBaseUri, $languageSlug, $responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 frontend cookie is sent to the client');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click register link
        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2RegistrationUriId, $responseData);
        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest($siteBaseUri . $oauth2RegistrationUri, $responseData['cookieData']), false);
        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertEquals($expectedRemoteInstanceHost, $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals($expectedRemoteClientId, $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals($siteBaseUri . $languageSlug . '/_oauth2?oauth2-provider=' . $expectedProvider . '&tx_oauth2client%5Baction%5D=verify', $authorizationQuery['redirect_uri'] ?? null, 'assert: the correct callback uri is sent');
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(1, $oauth2FrontendSessionData, 'assert: 1 oauth2 frontend session was created');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertEquals(0, (int)$oauth2FrontendSessionData[0]['ses_userid'], 'assert: the frontend session is not connected to a user');
        self::assertEquals(
            [
                'oauth2-original-registration-request-data' => [
                    'protocolVersion' => '1.1',
                    'method' => 'GET',
                    'uri'=> $siteBaseUri . $languageSlug . '/manage-providers',
                    'headers' => [
                        'user-agent' => ['TYPO3 Functional Test Request'],
                        'host' => [$siteHost],
                    ],
                    'parsedBody' => []
                ],
                'oauth2-state' => $requestedState
            ],
            $oauth2FrontendSessionData[0]['ses_data'],
            'assert: the frontend session contains the oauth2 state, which is also contained in the authorization request uri and the original request data'
        );

        // Simulate the redirect from the remote instance back to the TYPO3
        $requestedCallbackUri = new Uri(urldecode($authorizationQuery['redirect_uri']));
        parse_str($requestedCallbackUri->getQuery(), $respondedCallbackQuery);
        $respondedCallbackQuery['oauth2-provider'] = $remoteRequestData['oauth2-provider'];
        $respondedCallbackQuery['state'] = $remoteRequestData['oauth2-state'] === '_state' ? $requestedState : $remoteRequestData['oauth2-state'];
        $respondedCallbackQuery['code'] = $remoteRequestData['oauth2-code'];

        if ($clearSession) {
            $this->removeOauth2FrontendSessionData();
        }

        $respondedCallbackUri = (string)$requestedCallbackUri->withQuery(http_build_query($respondedCallbackQuery));

        // At this point we were redirected back from the remote instance to the TYPO3
        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part
        $responseData = $this->fetchFrontendPageContens(
            $this->buildGetRequest($respondedCallbackUri, $responseData['cookieData']),
            false,
            $this->buildRequestContext(['X_TYPO3_TESTING_FRAMEWORK' => ['HTTP' => ['mocks' => [
                'className' => $remoteRequestData['oauth2-code'] === '_invalid' ? InvalidAccessTokenResponseFromGitLabHttpMock::class : SuccessfulGetUserFromGitLabHttpMock::class,
                'options' => [
                    'remoteUser' => $remoteUserData,
                ],
            ]]]])
        );

        $frontendUserSessionData = $this->getFrontendSessionDataByUser($frontendUserUid);
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();
        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        $redirectUri = new Uri($responseData['response']->getHeaderLine('location'));

        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the frontend oauth2 cookies is deleted');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertCount(0, $frontendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for frontend users');
        self::assertCount(0, $backendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for backend users');

        if (empty($remoteRequestData['oauth2-provider']) || $remoteRequestData['oauth2-provider'] === 'notconfigured') {
            self::assertEquals(401, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');
        } else {
            self::assertEquals(302, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');
            self::assertEquals($siteHost, $redirectUri->getHost(), 'assert: we are redirected to the right host');
            if ($clearSession) {
                self::assertEquals($languageSlug, $redirectUri->getPath(), 'assert: we are redirected to the OAuth2 provider management module');
            } else {
                self::assertEquals($languageSlug . '/manage-providers', $redirectUri->getPath(), 'assert: we are redirected to the OAuth2 provider management module');
            }
            self::assertEquals(
                'OAuth2: Not logged in or invalid data',
                $responseData['response']->getReasonPhrase(),
                'assert: response redirect reason is set'
            );
        }
    }

    /**
     * @test
     */
    public function assertThatAFrontendUserIsAbleToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendIfOAuth2SessionIsExpiredBeforeRegisterAction(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $oauth2RegistrationUriId = 'oauth2test-register-gitlab3-both';
        $siteHost = self::SITE1_HOST;
        $siteBaseUri = self::SITE1_BASE_URI;
        $languageSlug = '/en';
        $expectedProvider = 'gitlab3-both';
        $expectedRemoteInstanceHost = 'gitlab3';
        $expectedRemoteClientId = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';
        $remoteUserData = [
            'id' => 'user1-gitlab3-both-remote-identity',
            'username' => 'user1-gitlab3-both',
            'name' => 'user1 gitlab3-both',
            'email' => 'user1-gitlab3-both@waldhacker.dev',
        ];
        $frontendUserUid = 1000;

        // Login into frontend
        $responseData = $this->loginIntoFrontendWithUsernameAndPassword($siteBaseUri, $languageSlug, 'user1', 'password');
        // assert: no oauth2 cookie is sent to the client
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData']);
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData']);

        $responseData = $this->goToOauth2ProvidersFrontendPage($siteBaseUri, $languageSlug, $responseData);
        // assert: no oauth2 backend cookie is sent to the client
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData']);
        // assert: the oauth2 frontend cookie is sent to the client
        self::assertArrayHasKey('fe_typo_user_oauth2', $responseData['cookieData']);

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click register link
        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2RegistrationUriId, $responseData);

        $this->removeOauth2FrontendSessionData();

        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest($siteBaseUri . $oauth2RegistrationUri, $responseData['cookieData']), false);

        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertEquals($expectedRemoteInstanceHost, $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals($expectedRemoteClientId, $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals($siteBaseUri . $languageSlug . '/_oauth2?oauth2-provider=' . $expectedProvider . '&tx_oauth2client%5Baction%5D=verify', $authorizationQuery['redirect_uri'] ?? null, 'assert: the correct callback uri is sent');
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(1, $oauth2FrontendSessionData, 'assert: 1 oauth2 frontend session was created');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertEquals(0, (int)$oauth2FrontendSessionData[0]['ses_userid'], 'assert: the frontend session is not connected to a user');
        self::assertEquals(
            [
                'oauth2-state' => $requestedState
            ],
            $oauth2FrontendSessionData[0]['ses_data'],
            'assert: the frontend session contains the oauth2 state, which is also contained in the authorization request uri'
        );

        // Simulate the redirect from the remote instance back to the TYPO3
        $requestedCallbackUri = new Uri(urldecode($authorizationQuery['redirect_uri']));
        parse_str($requestedCallbackUri->getQuery(), $respondedCallbackQuery);
        $respondedCallbackQuery['state'] = $requestedState;
        $respondedCallbackQuery['code'] = 'some-remote-api-access-code';
        $respondedCallbackUri = (string)$requestedCallbackUri->withQuery(http_build_query($respondedCallbackQuery));

        // At this point we were redirected back from the remote instance to the TYPO3
        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part
        $responseData = $this->fetchFrontendPageContens(
            $this->buildGetRequest($respondedCallbackUri, $responseData['cookieData']),
            false,
            $this->buildRequestContext(['X_TYPO3_TESTING_FRAMEWORK' => ['HTTP' => ['mocks' => [
                // successful Oauth2Service::getUser() request
                'className' => SuccessfulGetUserFromGitLabHttpMock::class,
                'options' => [
                    'remoteUser' => $remoteUserData,
                ],
            ]]]])
        );

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();
        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        $redirectUri = new Uri($responseData['response']->getHeaderLine('location'));

        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the frontend oauth2 cookies is deleted');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertCount(1, $frontendUserOauth2ProviderConfigurations, 'assert: 1 oauth2 provider configuration was created for frontend users');
        self::assertCount(0, $backendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for backend users');
        self::assertEquals(0, (int)$frontendUserOauth2ProviderConfigurations[0]['pid'], 'assert: created frontend user oauth2 provider configuration is valid');
        self::assertEquals($frontendUserUid, (int)$frontendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: created frontend user oauth2 provider configuration is valid');
        self::assertEquals($expectedProvider, $frontendUserOauth2ProviderConfigurations[0]['provider'], 'assert: created frontend user oauth2 provider configuration is valid');
        self::assertEquals($remoteUserData['id'], $frontendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: created frontend user oauth2 provider configuration is valid');
        self::assertEquals($siteBaseUri . $languageSlug, (string)$redirectUri, 'assert: we are redirected to the base uri');
        self::assertEquals(
            'OAuth2: Done, but unable to find the original requested location',
            $responseData['response']->getReasonPhrase(),
            'assert: response redirect reason is set'
        );
        self::assertEquals(302, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');
    }

    public function assertThatAFrontendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendIfUserLoginIsExpiredBeforeRegisterActionDataProvider(): \Generator
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
     * @dataProvider assertThatAFrontendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendIfUserLoginIsExpiredBeforeRegisterActionDataProvider
     */
    public function assertThatAFrontendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendIfUserLoginIsExpiredBeforeRegisterAction(bool $expiredByCookie): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $oauth2RegistrationUriId = 'oauth2test-register-gitlab3-both';
        $siteHost = self::SITE1_HOST;
        $siteBaseUri = self::SITE1_BASE_URI;
        $languageSlug = '/en';
        $expectedProvider = 'gitlab3-both';
        $expectedRemoteInstanceHost = 'gitlab3';
        $expectedRemoteClientId = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';
        $remoteUserData = [
            'id' => 'user1-gitlab3-both-remote-identity',
            'username' => 'user1-gitlab3-both',
            'name' => 'user1 gitlab3-both',
            'email' => 'user1-gitlab3-both@waldhacker.dev',
        ];

        // Login into frontend
        $responseData = $this->loginIntoFrontendWithUsernameAndPassword($siteBaseUri, $languageSlug, 'user1', 'password');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersFrontendPage($siteBaseUri, $languageSlug, $responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 frontend cookie is sent to the client');

        $frontendUserCookieData = $responseData['cookieData']['fe_typo_user'];

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click register link
        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2RegistrationUriId, $responseData);

        if ($expiredByCookie) {
            unset($responseData['cookieData']['fe_typo_user']);
        } else {
            $this->resetSessionData();
        }

        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest($siteBaseUri . $oauth2RegistrationUri, $responseData['cookieData']), false);

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();
        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();

        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');

        if ($expiredByCookie) {
            self::assertArrayNotHasKey('fe_typo_user', $responseData['cookieData'], 'assert: no frontend user cookie is sent to the client');
        } else {
            self::assertArrayHasKey('fe_typo_user', $responseData['cookieData'], 'assert: the frontend user cookie is sent to the client');
            self::assertEquals($frontendUserCookieData, $responseData['cookieData']['fe_typo_user'], 'assert: no new frontend user cookie is created');
        }

        self::assertFalse($responseData['response']->hasHeader('location'), 'assert: no redirect is made');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: the oauth2 frontend session still exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertCount(0, $frontendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for frontend users');
        self::assertCount(0, $backendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for backend users');
    }

    public function assertThatAFrontendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendIfUserLoginIsExpiredBeforeVerifyActionDataProvider(): \Generator
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
     * @dataProvider assertThatAFrontendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendIfUserLoginIsExpiredBeforeVerifyActionDataProvider
     */
    public function assertThatAFrontendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendIfUserLoginIsExpiredBeforeVerifyAction(bool $expiredByCookie): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $oauth2RegistrationUriId = 'oauth2test-register-gitlab3-both';
        $siteHost = self::SITE1_HOST;
        $siteBaseUri = self::SITE1_BASE_URI;
        $languageSlug = '/en';
        $expectedProvider = 'gitlab3-both';
        $expectedRemoteInstanceHost = 'gitlab3';
        $expectedRemoteClientId = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';
        $remoteUserData = [
            'id' => 'user1-gitlab3-both-remote-identity',
            'username' => 'user1-gitlab3-both',
            'name' => 'user1 gitlab3-both',
            'email' => 'user1-gitlab3-both@waldhacker.dev',
        ];

        // Login into frontend
        $responseData = $this->loginIntoFrontendWithUsernameAndPassword($siteBaseUri, $languageSlug, 'user1', 'password');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersFrontendPage($siteBaseUri, $languageSlug, $responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 frontend cookie is sent to the client');

        $frontendUserCookieData = $responseData['cookieData']['fe_typo_user'];

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click register link
        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2RegistrationUriId, $responseData);
        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest($siteBaseUri . $oauth2RegistrationUri, $responseData['cookieData']), false);
        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertEquals($expectedRemoteInstanceHost, $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals($expectedRemoteClientId, $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals($siteBaseUri . $languageSlug . '/_oauth2?oauth2-provider=' . $expectedProvider . '&tx_oauth2client%5Baction%5D=verify', $authorizationQuery['redirect_uri'] ?? null, 'assert: the correct callback uri is sent');
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(1, $oauth2FrontendSessionData, 'assert: 1 oauth2 frontend session was created');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertEquals(0, (int)$oauth2FrontendSessionData[0]['ses_userid'], 'assert: the frontend session is not connected to a user');
        self::assertEquals(
            [
                'oauth2-original-registration-request-data' => [
                    'protocolVersion' => '1.1',
                    'method' => 'GET',
                    'uri'=> $siteBaseUri . $languageSlug . '/manage-providers',
                    'headers' => [
                        'user-agent' => ['TYPO3 Functional Test Request'],
                        'host' => [$siteHost],
                    ],
                    'parsedBody' => []
                ],
                'oauth2-state' => $requestedState
            ],
            $oauth2FrontendSessionData[0]['ses_data'],
            'assert: the frontend session contains the oauth2 state, which is also contained in the authorization request uri and the original request data'
        );

        // Simulate the redirect from the remote instance back to the TYPO3
        $requestedCallbackUri = new Uri(urldecode($authorizationQuery['redirect_uri']));
        parse_str($requestedCallbackUri->getQuery(), $respondedCallbackQuery);
        $respondedCallbackQuery['state'] = $requestedState;
        $respondedCallbackQuery['code'] = 'some-remote-api-access-code';
        $respondedCallbackUri = (string)$requestedCallbackUri->withQuery(http_build_query($respondedCallbackQuery));

        if ($expiredByCookie) {
            unset($responseData['cookieData']['fe_typo_user']);
        } else {
            $this->resetSessionData();
        }

        // At this point we were redirected back from the remote instance to the TYPO3
        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part
        $responseData = $this->fetchFrontendPageContens(
            $this->buildGetRequest($respondedCallbackUri, $responseData['cookieData']),
            false,
            $this->buildRequestContext(['X_TYPO3_TESTING_FRAMEWORK' => ['HTTP' => ['mocks' => [
                // successful Oauth2Service::getUser() request
                'className' => SuccessfulGetUserFromGitLabHttpMock::class,
                'options' => [
                    'remoteUser' => $remoteUserData,
                ],
            ]]]])
        );

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();
        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();

        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');

        if ($expiredByCookie) {
            self::assertArrayNotHasKey('fe_typo_user', $responseData['cookieData'], 'assert: no frontend user cookie is sent to the client');
        } else {
            self::assertArrayHasKey('fe_typo_user', $responseData['cookieData'], 'assert: the frontend user cookie is sent to the client');
            self::assertEquals($frontendUserCookieData, $responseData['cookieData']['fe_typo_user'], 'assert: no new frontend user cookie is created');
        }

        self::assertEquals(401, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: the oauth2 frontend session still exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertCount(0, $frontendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for frontend users');
        self::assertCount(0, $backendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for backend users');
    }

    /**
     * @test
     */
    public function assertThatAFrontendUserIsUnableToActivateAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendIfTheTYPO3UserIsWithinANotAllowedStorage(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $oauth2RegistrationUriId = 'oauth2test-register-gitlab3-both';
        $siteHost = self::SITE1_HOST;
        $siteBaseUri = self::SITE1_BASE_URI;
        $languageSlug = '/en';
        $expectedProvider = 'gitlab3-both';
        $expectedRemoteInstanceHost = 'gitlab3';
        $expectedRemoteClientId = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';
        $remoteUserData = [
            'id' => 'user1-gitlab3-both-remote-identity',
            'username' => 'user1-gitlab3-both',
            'name' => 'user1 gitlab3-both',
            'email' => 'user1-gitlab3-both@waldhacker.dev',
        ];

        // Login into frontend
        $responseData = $this->loginIntoFrontendWithUsernameAndPassword($siteBaseUri, $languageSlug, 'user7', 'password');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersFrontendPage($siteBaseUri, $languageSlug, $responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 frontend cookie is sent to the client');

        $frontendUserCookieData = $responseData['cookieData']['fe_typo_user'];

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click register link
        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2RegistrationUriId, $responseData);
        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest($siteBaseUri . $oauth2RegistrationUri, $responseData['cookieData']), false);
        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertEquals($expectedRemoteInstanceHost, $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals($expectedRemoteClientId, $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals($siteBaseUri . $languageSlug . '/_oauth2?oauth2-provider=' . $expectedProvider . '&tx_oauth2client%5Baction%5D=verify', $authorizationQuery['redirect_uri'] ?? null, 'assert: the correct callback uri is sent');
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(1, $oauth2FrontendSessionData, 'assert: 1 oauth2 frontend session was created');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertEquals(0, (int)$oauth2FrontendSessionData[0]['ses_userid'], 'assert: the frontend session is not connected to a user');
        self::assertEquals(
            [
                'oauth2-state' => $requestedState
            ],
            $oauth2FrontendSessionData[0]['ses_data'],
            'assert: the frontend session contains the oauth2 state'
        );

        // Simulate the redirect from the remote instance back to the TYPO3
        $requestedCallbackUri = new Uri(urldecode($authorizationQuery['redirect_uri']));
        parse_str($requestedCallbackUri->getQuery(), $respondedCallbackQuery);
        $respondedCallbackQuery['state'] = $requestedState;
        $respondedCallbackQuery['code'] = 'some-remote-api-access-code';
        $respondedCallbackUri = (string)$requestedCallbackUri->withQuery(http_build_query($respondedCallbackQuery));

        // At this point we were redirected back from the remote instance to the TYPO3
        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part
        $responseData = $this->fetchFrontendPageContens(
            $this->buildGetRequest($respondedCallbackUri, $responseData['cookieData']),
            false,
            $this->buildRequestContext(['X_TYPO3_TESTING_FRAMEWORK' => ['HTTP' => ['mocks' => [
                // successful Oauth2Service::getUser() request
                'className' => SuccessfulGetUserFromGitLabHttpMock::class,
                'options' => [
                    'remoteUser' => $remoteUserData,
                ],
            ]]]])
        );

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();
        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        $redirectUri = new Uri($responseData['response']->getHeaderLine('location'));

        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayHasKey('fe_typo_user', $responseData['cookieData'], 'assert: the frontend user cookie is sent to the client');
        self::assertEquals($frontendUserCookieData, $responseData['cookieData']['fe_typo_user'], 'assert: no new frontend user cookie is created');

        self::assertEquals(302, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');
        self::assertEquals($siteBaseUri . $languageSlug, (string)$redirectUri, 'assert: we are redirected to the base uri');
        self::assertEquals(
            'OAuth2: Not logged in or invalid data',
            $responseData['response']->getReasonPhrase(),
            'assert: response redirect reason is set'
        );

        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertCount(0, $frontendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for frontend users');
        self::assertCount(0, $backendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for backend users');
    }

    private function goToOauth2ProvidersFrontendPage(string $siteBaseUri, string $languageSlug, array $responseData): array
    {
        $uri = $siteBaseUri . $languageSlug . '/manage-providers';
        return $this->fetchFrontendPageContens($this->buildGetRequest($uri, $responseData['cookieData']));
    }
}
