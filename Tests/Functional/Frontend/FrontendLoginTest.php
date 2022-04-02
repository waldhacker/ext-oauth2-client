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

class FrontendLoginTest extends FunctionalTestCase
{
    public function assertThatAFrontendUserIsAbleToLoginWithAOAuth2ProviderWhichConfiguredToBeUsedWithinTheFrontendAndActivatedByTheUserDataProvider(): \Generator
    {
        yield 'site1 EN with gitlab3-both' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab3-both',
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
            'oauth2LoginUriId' => 'oauth2test-login-gitlab1-fe',
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
            'oauth2LoginUriId' => 'oauth2test-login-gitlab6-both',
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
            'oauth2LoginUriId' => 'oauth2test-login-gitlab1-fe',
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
     * @dataProvider assertThatAFrontendUserIsAbleToLoginWithAOAuth2ProviderWhichConfiguredToBeUsedWithinTheFrontendAndActivatedByTheUserDataProvider
     */
    public function assertThatAFrontendUserIsAbleToLoginWithAOAuth2ProviderWhichConfiguredToBeUsedWithinTheFrontendAndActivatedByTheUser(
        string $oauth2LoginUriId,
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
        $this->createFrontendUserOauth2ProviderConfiguration(100, 1000, $expectedProvider, $remoteUserData['id']);

        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click "login with gitlab3-both"
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2LoginUriId, $responseData);
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
        self::assertEquals($siteBaseUri . $languageSlug . '/_oauth2?oauth2-provider=' . $expectedProvider . '&logintype=login', $authorizationQuery['redirect_uri'] ?? null, 'assert: the correct callback uri is sent');
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(1, $oauth2FrontendSessionData, 'assert: 1 oauth2 frontend session was created');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertEquals(0, (int)$oauth2FrontendSessionData[0]['ses_userid'], 'assert: the frontend session is not connected to a user');
        self::assertEquals(
            [
                'oauth2-state' => $requestedState,
                'oauth2-original-registration-request-data' => [
                    'protocolVersion' => '1.1',
                    'method' => 'GET',
                    'uri' => $siteBaseUri . $oauth2RegistrationUri,
                    'headers' => [
                        'user-agent' => ['TYPO3 Functional Test Request'],
                        'host' => [$siteHost],
                    ],
                    'parsedBody' => [],
                ],
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
        $redirectUri = new Uri($responseData['response']->getHeaderLine('location'));

        $expectedRedirectUri = new Uri($siteBaseUri . $oauth2RegistrationUri);
        parse_str($expectedRedirectUri->getQuery(), $queryParameters);
        unset($queryParameters['oauth2-provider'], $queryParameters['after-oauth2-redirect-uri'], $queryParameters['logintype']);
        $expectedRedirectUri = $expectedRedirectUri->withQuery(http_build_query($queryParameters));

        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the frontend oauth2 cookies is deleted');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayHasKey('fe_typo_user', $responseData['cookieData'], 'assert: the frontend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertEquals($expectedRedirectUri, (string)$redirectUri, 'assert: we are redirected to the TYPO3 frontend');
        self::assertEquals(
            'OAuth2: Done. Redirection to original requested location',
            $responseData['response']->getReasonPhrase(),
            'assert: response redirect reason is set'
        );
        self::assertEquals(302, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');

        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest((string)$redirectUri, $responseData['cookieData']));
        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug, $responseData);
        self::assertStringContainsString('Login CE', $responseData['pageMarkup'], 'assert: we are logged in');
        self::assertStringContainsString('type="submit" value="Logout"', $responseData['pageMarkup'], 'assert: we are logged in');
    }

    public function assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsNotConfiguredToBeUsedWithinTheFrontendOrSiteButActivatedByTheUserDataProvider(): \Generator
    {
        yield 'site1 EN - login with a backend provider (gitlab2-be)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab2-be',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/en',
            'providerId' => 'gitlab2-be',
            'remoteIdentity' => 'user1-gitlab2-be-remote-identity',
        ];

        yield 'site1 EN - login with a frontend provider from site2 EN (gitlab6-both)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab6-both',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/en',
            'providerId' => 'gitlab6-both',
            'remoteIdentity' => 'user1-gitlab6-both-remote-identity',
        ];

        yield 'site1 EN - login with a frontend provider from site1 DE (gitlab4-fe)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab4-fe',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/en',
            'providerId' => 'gitlab4-fe',
            'remoteIdentity' => 'user1-gitlab4-fe-remote-identity',
        ];

        yield 'site1 EN - login with a frontend provider from site2 DE (gitlab7-fe)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab7-fe',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/en',
            'providerId' => 'gitlab7-fe',
            'remoteIdentity' => 'user1-gitlab7-fe-remote-identity',
        ];

        yield 'site1 EN - login with a not configured provider (notconfigured)' => [
            'oauth2LoginUriId' => 'oauth2test-login-notconfigured',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/en',
            'providerId' => 'notconfigured',
            'remoteIdentity' => 'user1-notconfigured-remote-identity',
        ];

        yield 'site1 EN - login with a not configured provider (_invalid_)' => [
            'oauth2LoginUriId' => 'oauth2test-login-_invalid_',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/en',
            'providerId' => '_invalid_',
            'remoteIdentity' => 'user1-_invalid_-remote-identity',
        ];

        yield 'site1 DE - login with a backend provider (gitlab2-be)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab2-be',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/de',
            'providerId' => 'gitlab2-be',
            'remoteIdentity' => 'user1-gitlab2-be-remote-identity',
        ];

        yield 'site1 DE - login with a frontend provider from site2 DE (gitlab7-fe)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab7-fe',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/de',
            'providerId' => 'gitlab7-fe',
            'remoteIdentity' => 'user1-gitlab7-fe-remote-identity',
        ];

        yield 'site1 DE - login with a frontend provider from site1 EN (gitlab3-both)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab3-both',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/de',
            'providerId' => 'gitlab3-both',
            'remoteIdentity' => 'user1-gitlab3-both-remote-identity',
        ];

        yield 'site1 DE - login with a frontend provider from site2 EN (gitlab6-both)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab6-both',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/de',
            'providerId' => 'gitlab6-both',
            'remoteIdentity' => 'user1-gitlab6-both-remote-identity',
        ];

        yield 'site1 DE - login with a not configured provider (notconfigured)' => [
            'oauth2LoginUriId' => 'oauth2test-login-notconfigured',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/de',
            'providerId' => 'notconfigured',
            'remoteIdentity' => 'user1-notconfigured-remote-identity',
        ];

        yield 'site1 DE - login with a not configured provider (_invalid_)' => [
            'oauth2LoginUriId' => 'oauth2test-login-_invalid_',
            'siteBaseUri' => self::SITE1_BASE_URI,
            'languageSlug' => '/de',
            'providerId' => '_invalid_',
            'remoteIdentity' => 'user1-_invalid_-remote-identity',
        ];

        yield 'site2 EN - login with a backend provider (gitlab2-be)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab2-be',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/en',
            'providerId' => 'gitlab2-be',
            'remoteIdentity' => 'user1-gitlab2-be-remote-identity',
        ];

        yield 'site2 EN - login with a frontend provider from site1 EN (gitlab3-both)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab3-both',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/en',
            'providerId' => 'gitlab3-both',
            'remoteIdentity' => 'user1-gitlab3-both-remote-identity',
        ];

        yield 'site2 EN - login with a frontend provider from site2 DE (gitlab1-fe)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab1-fe',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/en',
            'providerId' => 'gitlab1-fe',
            'remoteIdentity' => 'user1-gitlab1-fe-remote-identity',
        ];

        yield 'site2 EN - login with a frontend provider from site1 DE (gitlab8-fe)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab8-fe',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/en',
            'providerId' => 'gitlab8-fe',
            'remoteIdentity' => 'user1-gitlab8-fe-remote-identity',
        ];

        yield 'site2 EN - login with a not configured provider (notconfigured)' => [
            'oauth2LoginUriId' => 'oauth2test-login-notconfigured',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/en',
            'providerId' => 'notconfigured',
            'remoteIdentity' => 'user1-notconfigured-remote-identity',
        ];

        yield 'site2 EN - login with a not configured provider (_invalid_)' => [
            'oauth2LoginUriId' => 'oauth2test-login-_invalid_',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/en',
            'providerId' => '_invalid_',
            'remoteIdentity' => 'user1-_invalid_-remote-identity',
        ];

        yield 'site2 DE - login with a backend provider (gitlab2-be)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab2-be',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/de',
            'providerId' => 'gitlab2-be',
            'remoteIdentity' => 'user1-gitlab2-be-remote-identity',
        ];

        yield 'site2 DE - login with a frontend provider from site1 DE (gitlab8-fe)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab6-both',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/de',
            'providerId' => 'gitlab6-both',
            'remoteIdentity' => 'user1-gitlab6-both-remote-identity',
        ];

        yield 'site2 DE - login with a frontend provider from site2 EN (gitlab6-both)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab6-both',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/de',
            'providerId' => 'gitlab6-both',
            'remoteIdentity' => 'user1-gitlab6-both-remote-identity',
        ];

        yield 'site2 DE - login with a frontend provider from site1 EN (gitlab3-both)' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab3-both',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/de',
            'providerId' => 'gitlab3-both',
            'remoteIdentity' => 'user1-gitlab3-both-remote-identity',
        ];

        yield 'site2 DE - login with a not configured provider (notconfigured)' => [
            'oauth2LoginUriId' => 'oauth2test-login-notconfigured',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/de',
            'providerId' => 'notconfigured',
            'remoteIdentity' => 'user1-notconfigured-remote-identity',
        ];

        yield 'site2 DE - login with a not configured provider (_invalid_)' => [
            'oauth2LoginUriId' => 'oauth2test-login-_invalid_',
            'siteBaseUri' => self::SITE2_BASE_URI,
            'languageSlug' => '/de',
            'providerId' => '_invalid_',
            'remoteIdentity' => 'user1-_invalid_-remote-identity',
        ];
    }

    /**
     * @test
     * @dataProvider assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsNotConfiguredToBeUsedWithinTheFrontendOrSiteButActivatedByTheUserDataProvider
     */
    public function assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsNotConfiguredToBeUsedWithinTheFrontendOrSiteButActivatedByTheUser(
        string $oauth2LoginUriId,
        string $siteBaseUri,
        string $languageSlug,
        string $providerId,
        string $remoteIdentity
    ): void {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createFrontendUserOauth2ProviderConfiguration(100, 1000, $providerId, $remoteIdentity);

        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click login link
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2LoginUriId, $responseData);
        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest($siteBaseUri . $oauth2RegistrationUri, $responseData['cookieData']), false);

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie exists');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user', $responseData['cookieData'], 'assert: no frontend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');

        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug, $responseData);
        self::assertStringContainsString('Login CE', $responseData['pageMarkup'], 'assert: we are on the login page');
        self::assertStringNotContainsString('type="submit" value="Logout"', $responseData['pageMarkup'], 'assert: we are not logged in');
    }

    /**
     * @test
     */
    public function assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendOrSiteButActivatedBy2TYPO3Users(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $oauth2LoginUriId = 'oauth2test-login-gitlab3-both';
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

        $this->createFrontendUserOauth2ProviderConfiguration(100, 1000, $expectedProvider, $remoteUserData['id']);
        $this->createFrontendUserOauth2ProviderConfiguration(101, 1001, $expectedProvider, $remoteUserData['id']);

        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click login link
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2LoginUriId, $responseData);
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
        self::assertEquals($siteBaseUri . $languageSlug . '/_oauth2?oauth2-provider=' . $expectedProvider . '&logintype=login', $authorizationQuery['redirect_uri'] ?? null, 'assert: the correct callback uri is sent');
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(1, $oauth2FrontendSessionData, 'assert: 1 oauth2 frontend session was created');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertEquals(0, (int)$oauth2FrontendSessionData[0]['ses_userid'], 'assert: the frontend session is not connected to a user');
        self::assertEquals(
            [
                'oauth2-state' => $requestedState,
                'oauth2-original-registration-request-data' => [
                    'protocolVersion' => '1.1',
                    'method' => 'GET',
                    'uri' => $siteBaseUri . $oauth2RegistrationUri,
                    'headers' => [
                        'user-agent' => ['TYPO3 Functional Test Request'],
                        'host' => [$siteHost],
                    ],
                    'parsedBody' => [],
                ],
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
        $redirectUri = new Uri($responseData['response']->getHeaderLine('location'));

        $expectedRedirectUri = new Uri($siteBaseUri . $oauth2RegistrationUri);
        parse_str($expectedRedirectUri->getQuery(), $queryParameters);
        unset($queryParameters['oauth2-provider'], $queryParameters['after-oauth2-redirect-uri'], $queryParameters['logintype']);
        $expectedRedirectUri = $expectedRedirectUri->withQuery(http_build_query($queryParameters));

        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the frontend oauth2 cookies is deleted');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user', $responseData['cookieData'], 'assert: no frontend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertEquals($expectedRedirectUri, (string)$redirectUri, 'assert: we are redirected to the TYPO3 frontend');
        self::assertEquals(
            'OAuth2: Done. Redirection to original requested location',
            $responseData['response']->getReasonPhrase(),
            'assert: response redirect reason is set'
        );
        self::assertEquals(302, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');

        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest((string)$redirectUri, $responseData['cookieData']));
        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug, $responseData);
        self::assertStringContainsString('Login CE', $responseData['pageMarkup'], 'assert: we are on the login page');
        self::assertStringNotContainsString('type="submit" value="Logout"', $responseData['pageMarkup'], 'assert: we are not logged in');
    }

    /**
     * @test
     */
    public function assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendOrSiteButActivated2Times(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $oauth2LoginUriId = 'oauth2test-login-gitlab3-both';
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

        $this->createFrontendUserOauth2ProviderConfiguration(100, 1000, $expectedProvider, $remoteUserData['id']);
        $this->createFrontendUserOauth2ProviderConfiguration(101, 1000, $expectedProvider, $remoteUserData['id']);

        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click login link
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2LoginUriId, $responseData);
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
        self::assertEquals($siteBaseUri . $languageSlug . '/_oauth2?oauth2-provider=' . $expectedProvider . '&logintype=login', $authorizationQuery['redirect_uri'] ?? null, 'assert: the correct callback uri is sent');
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(1, $oauth2FrontendSessionData, 'assert: 1 oauth2 frontend session was created');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertEquals(0, (int)$oauth2FrontendSessionData[0]['ses_userid'], 'assert: the frontend session is not connected to a user');
        self::assertEquals(
            [
                'oauth2-state' => $requestedState,
                'oauth2-original-registration-request-data' => [
                    'protocolVersion' => '1.1',
                    'method' => 'GET',
                    'uri' => $siteBaseUri . $oauth2RegistrationUri,
                    'headers' => [
                        'user-agent' => ['TYPO3 Functional Test Request'],
                        'host' => [$siteHost],
                    ],
                    'parsedBody' => [],
                ],
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
        $redirectUri = new Uri($responseData['response']->getHeaderLine('location'));

        $expectedRedirectUri = new Uri($siteBaseUri . $oauth2RegistrationUri);
        parse_str($expectedRedirectUri->getQuery(), $queryParameters);
        unset($queryParameters['oauth2-provider'], $queryParameters['after-oauth2-redirect-uri'], $queryParameters['logintype']);
        $expectedRedirectUri = $expectedRedirectUri->withQuery(http_build_query($queryParameters));

        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the frontend oauth2 cookies is deleted');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user', $responseData['cookieData'], 'assert: no frontend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertEquals($expectedRedirectUri, (string)$redirectUri, 'assert: we are redirected to the TYPO3 frontend');
        self::assertEquals(
            'OAuth2: Done. Redirection to original requested location',
            $responseData['response']->getReasonPhrase(),
            'assert: response redirect reason is set'
        );
        self::assertEquals(302, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');

        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest((string)$redirectUri, $responseData['cookieData']));
        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug, $responseData);
        self::assertStringContainsString('Login CE', $responseData['pageMarkup'], 'assert: we are on the login page');
        self::assertStringNotContainsString('type="submit" value="Logout"', $responseData['pageMarkup'], 'assert: we are not logged in');
    }

    /**
     * @test
     */
    public function assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendOrSiteButActivationIsInvalid(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $oauth2LoginUriId = 'oauth2test-login-gitlab3-both';
        $siteHost = self::SITE1_HOST;
        $siteBaseUri = self::SITE1_BASE_URI;
        $languageSlug = '/en';
        $expectedProvider = 'gitlab3-both';
        $expectedRemoteInstanceHost = 'gitlab3';
        $expectedRemoteClientId = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';
        $remoteUserData = [
            'id' => '_invalid_',
            'username' => 'user1-gitlab3-both',
            'name' => 'user1 gitlab3-both',
            'email' => 'user1-gitlab3-both@waldhacker.dev',
        ];

        $this->createFrontendUserOauth2ProviderConfiguration(100, 1000, $expectedProvider, $remoteUserData['id']);
        $this->createFrontendUserOauth2ProviderConfiguration(101, 1000, $expectedProvider, $remoteUserData['id']);

        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click login link
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2LoginUriId, $responseData);
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
        self::assertEquals($siteBaseUri . $languageSlug . '/_oauth2?oauth2-provider=' . $expectedProvider . '&logintype=login', $authorizationQuery['redirect_uri'] ?? null, 'assert: the correct callback uri is sent');
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(1, $oauth2FrontendSessionData, 'assert: 1 oauth2 frontend session was created');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertEquals(0, (int)$oauth2FrontendSessionData[0]['ses_userid'], 'assert: the frontend session is not connected to a user');
        self::assertEquals(
            [
                'oauth2-state' => $requestedState,
                'oauth2-original-registration-request-data' => [
                    'protocolVersion' => '1.1',
                    'method' => 'GET',
                    'uri' => $siteBaseUri . $oauth2RegistrationUri,
                    'headers' => [
                        'user-agent' => ['TYPO3 Functional Test Request'],
                        'host' => [$siteHost],
                    ],
                    'parsedBody' => [],
                ],
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
        $redirectUri = new Uri($responseData['response']->getHeaderLine('location'));

        $expectedRedirectUri = new Uri($siteBaseUri . $oauth2RegistrationUri);
        parse_str($expectedRedirectUri->getQuery(), $queryParameters);
        unset($queryParameters['oauth2-provider'], $queryParameters['after-oauth2-redirect-uri'], $queryParameters['logintype']);
        $expectedRedirectUri = $expectedRedirectUri->withQuery(http_build_query($queryParameters));

        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the frontend oauth2 cookies is deleted');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user', $responseData['cookieData'], 'assert: no frontend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertEquals($expectedRedirectUri, (string)$redirectUri, 'assert: we are redirected to the TYPO3 frontend');
        self::assertEquals(
            'OAuth2: Done. Redirection to original requested location',
            $responseData['response']->getReasonPhrase(),
            'assert: response redirect reason is set'
        );
        self::assertEquals(302, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');

        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest((string)$redirectUri, $responseData['cookieData']));
        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug, $responseData);
        self::assertStringContainsString('Login CE', $responseData['pageMarkup'], 'assert: we are on the login page');
        self::assertStringNotContainsString('type="submit" value="Logout"', $responseData['pageMarkup'], 'assert: we are not logged in');
    }

    public function assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendButNotActivatedByTheUserDataProvider(): \Generator
    {
        yield 'site1 EN with gitlab3-both' => [
            'oauth2LoginUriId' => 'oauth2test-login-gitlab3-both',
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
            'oauth2LoginUriId' => 'oauth2test-login-gitlab1-fe',
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
            'oauth2LoginUriId' => 'oauth2test-login-gitlab6-both',
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
            'oauth2LoginUriId' => 'oauth2test-login-gitlab1-fe',
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
     * @dataProvider assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendButNotActivatedByTheUserDataProvider
     */
    public function assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendButNotActivatedByTheUser(
        string $oauth2LoginUriId,
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

        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click login link
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2LoginUriId, $responseData);
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
        self::assertEquals($siteBaseUri . $languageSlug . '/_oauth2?oauth2-provider=' . $expectedProvider . '&logintype=login', $authorizationQuery['redirect_uri'] ?? null, 'assert: the correct callback uri is sent');
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(1, $oauth2FrontendSessionData, 'assert: 1 oauth2 frontend session was created');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertEquals(0, (int)$oauth2FrontendSessionData[0]['ses_userid'], 'assert: the frontend session is not connected to a user');
        self::assertEquals(
            [
                'oauth2-state' => $requestedState,
                'oauth2-original-registration-request-data' => [
                    'protocolVersion' => '1.1',
                    'method' => 'GET',
                    'uri' => $siteBaseUri . $oauth2RegistrationUri,
                    'headers' => [
                        'user-agent' => ['TYPO3 Functional Test Request'],
                        'host' => [$siteHost],
                    ],
                    'parsedBody' => [],
                ],
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
        $redirectUri = new Uri($responseData['response']->getHeaderLine('location'));

        $expectedRedirectUri = new Uri($siteBaseUri . $oauth2RegistrationUri);
        parse_str($expectedRedirectUri->getQuery(), $queryParameters);
        unset($queryParameters['oauth2-provider'], $queryParameters['after-oauth2-redirect-uri'], $queryParameters['logintype']);
        $expectedRedirectUri = $expectedRedirectUri->withQuery(http_build_query($queryParameters));

        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the frontend oauth2 cookies is deleted');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user', $responseData['cookieData'], 'assert: no frontend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertEquals($expectedRedirectUri, (string)$redirectUri, 'assert: we are redirected to the TYPO3 frontend');
        self::assertEquals(
            'OAuth2: Done. Redirection to original requested location',
            $responseData['response']->getReasonPhrase(),
            'assert: response redirect reason is set'
        );
        self::assertEquals(302, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');

        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest((string)$redirectUri, $responseData['cookieData']));
        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug, $responseData);
        self::assertStringContainsString('Login CE', $responseData['pageMarkup'], 'assert: we are on the login page');
        self::assertStringNotContainsString('type="submit" value="Logout"', $responseData['pageMarkup'], 'assert: we are not logged in');
    }

    public function assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendIfInvalidaDataIsSumbittedDataProvider(): \Generator
    {
        yield 'all empty' => [
            'remoteRequestData' => [
                'oauth2-provider' => '',
                'oauth2-state' => '',
                'oauth2-code' => '',
            ],
            'clearSession' => false,
        ];

        yield 'empty provider' => [
            'remoteRequestData' => [
                'oauth2-provider' => '',
                'oauth2-state' => '_state',
                'oauth2-code' => 'some-remote-api-access-code',
            ],
            'clearSession' => false,
        ];

        yield 'empty state' => [
            'remoteRequestData' => [
                'oauth2-provider' => 'gitlab3-both',
                'oauth2-state' => '',
                'oauth2-code' => 'some-remote-api-access-code',
            ],
            'clearSession' => false,
        ];

        yield 'empty code' => [
            'remoteRequestData' => [
                'oauth2-provider' => 'gitlab3-both',
                'oauth2-state' => '_state',
                'oauth2-code' => '',
            ],
            'clearSession' => false,
        ];

        yield 'unknown provider' => [
            'remoteRequestData' => [
                'oauth2-provider' => 'notconfigured',
                'oauth2-state' => '_state',
                'oauth2-code' => 'some-remote-api-access-code',
            ],
            'clearSession' => false,
        ];

        yield 'invalid code' => [
            'remoteRequestData' => [
                'oauth2-provider' => 'gitlab3-both',
                'oauth2-state' => '_state',
                'oauth2-code' => '_invalid',
            ],
            'clearSession' => false,
        ];

        yield 'invalid session data' => [
            'remoteRequestData' => [
                'oauth2-provider' => 'gitlab3-both',
                'oauth2-state' => '_state',
                'oauth2-code' => 'some-remote-api-access-code',
            ],
            'clearSession' => true,
        ];
    }

    /**
     * @test
     * @dataProvider assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendIfInvalidaDataIsSumbittedDataProvider
     */
    public function assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendIfInvalidaDataIsSumbitted(array $remoteRequestData, bool $clearSession): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createFrontendUserOauth2ProviderConfiguration(100, 1000, 'gitlab3-both', 'user1-gitlab3-both-remote-identity');

        $oauth2LoginUriId = 'oauth2test-login-gitlab3-both';
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

        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click "login with gitlab3-both"
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2LoginUriId, $responseData);
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
        self::assertEquals($siteBaseUri . $languageSlug . '/_oauth2?oauth2-provider=' . $expectedProvider . '&logintype=login', $authorizationQuery['redirect_uri'] ?? null, 'assert: the correct callback uri is sent');
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(1, $oauth2FrontendSessionData, 'assert: 1 oauth2 frontend session was created');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertEquals(0, (int)$oauth2FrontendSessionData[0]['ses_userid'], 'assert: the frontend session is not connected to a user');
        self::assertEquals(
            [
                'oauth2-state' => $requestedState,
                'oauth2-original-registration-request-data' => [
                    'protocolVersion' => '1.1',
                    'method' => 'GET',
                    'uri' => $siteBaseUri . $oauth2RegistrationUri,
                    'headers' => [
                        'user-agent' => ['TYPO3 Functional Test Request'],
                        'host' => [$siteHost],
                    ],
                    'parsedBody' => [],
                ],
            ],
            $oauth2FrontendSessionData[0]['ses_data'],
            'assert: the frontend session contains the oauth2 state, which is also contained in the authorization request uri and the original request data'
        );

        // Simulate the redirect from the remote instance back to the TYPO3
        $requestedCallbackUri = new Uri(urldecode($authorizationQuery['redirect_uri']));
        parse_str($requestedCallbackUri->getQuery(), $respondedCallbackQuery);
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
                    'remoteUser' => $remoteRequestData,
                ],
            ]]]])
        );

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();
        $redirectUri = new Uri($responseData['response']->getHeaderLine('location'));

        $expectedRedirectUri = new Uri($siteBaseUri . $oauth2RegistrationUri);
        parse_str($expectedRedirectUri->getQuery(), $queryParameters);
        unset($queryParameters['oauth2-provider'], $queryParameters['after-oauth2-redirect-uri'], $queryParameters['logintype']);
        $expectedRedirectUri = $expectedRedirectUri->withQuery(http_build_query($queryParameters));

        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the frontend oauth2 cookies is deleted');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user', $responseData['cookieData'], 'assert: no frontend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertEquals(302, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');
        if ($clearSession) {
            self::assertEquals($siteBaseUri . $languageSlug, (string)$redirectUri, 'assert: we are redirected to the TYPO3 frontend');
            self::assertEquals(
                'OAuth2: Done, but unable to find the original requested location',
                $responseData['response']->getReasonPhrase(),
                'assert: response redirect reason is set'
            );
        } else {
            self::assertEquals($expectedRedirectUri, (string)$redirectUri, 'assert: we are redirected to the TYPO3 frontend');
            self::assertEquals(
                'OAuth2: Done. Redirection to original requested location',
                $responseData['response']->getReasonPhrase(),
                'assert: response redirect reason is set'
            );
        }

        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest((string)$redirectUri, $responseData['cookieData']));
        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug, $responseData);
        self::assertStringContainsString('Login CE', $responseData['pageMarkup'], 'assert: we are on the login page');
        self::assertStringNotContainsString('type="submit" value="Logout"', $responseData['pageMarkup'], 'assert: we are not logged in');
    }

    public function assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendOrSiteAndActivatedButTheTYPO3UserIsInactiveOrWithinANotAllowedStorageDataProvider(): \Generator
    {
        yield 'inactive user' => [
            'userUid' => 1005,
        ];

        yield 'deleted user' => [
            'userUid' => 1006,
            'deleteUser' => true
        ];

        yield 'user within another storage' => [
            'userUid' => 1007,
        ];
    }

    /**
     * @test
     * @dataProvider assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendOrSiteAndActivatedButTheTYPO3UserIsInactiveOrWithinANotAllowedStorageDataProvider
     */
    public function assertThatAFrontendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheFrontendOrSiteAndActivatedButTheTYPO3UserIsInactiveOrWithinANotAllowedStorage(int $userUid, bool $deleteUser = false): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $oauth2LoginUriId = 'oauth2test-login-gitlab3-both';
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

        $this->createFrontendUserOauth2ProviderConfiguration(100, $userUid, $expectedProvider, $remoteUserData['id']);
        if ($deleteUser) {
            $this->deleteFrontendUser($userUid);
        }

        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 be cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 fe cookie is sent to the client');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        // Click login link
        $oauth2RegistrationUri = $this->extractLinkHrefFromResponseData($oauth2LoginUriId, $responseData);
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
        self::assertEquals($siteBaseUri . $languageSlug . '/_oauth2?oauth2-provider=' . $expectedProvider . '&logintype=login', $authorizationQuery['redirect_uri'] ?? null, 'assert: the correct callback uri is sent');
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(1, $oauth2FrontendSessionData, 'assert: 1 oauth2 frontend session was created');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertEquals(0, (int)$oauth2FrontendSessionData[0]['ses_userid'], 'assert: the frontend session is not connected to a user');
        self::assertEquals(
            [
                'oauth2-state' => $requestedState,
                'oauth2-original-registration-request-data' => [
                    'protocolVersion' => '1.1',
                    'method' => 'GET',
                    'uri' => $siteBaseUri . $oauth2RegistrationUri,
                    'headers' => [
                        'user-agent' => ['TYPO3 Functional Test Request'],
                        'host' => [$siteHost],
                    ],
                    'parsedBody' => [],
                ],
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
        $redirectUri = new Uri($responseData['response']->getHeaderLine('location'));

        $expectedRedirectUri = new Uri($siteBaseUri . $oauth2RegistrationUri);
        parse_str($expectedRedirectUri->getQuery(), $queryParameters);
        unset($queryParameters['oauth2-provider'], $queryParameters['after-oauth2-redirect-uri'], $queryParameters['logintype']);
        $expectedRedirectUri = $expectedRedirectUri->withQuery(http_build_query($queryParameters));

        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: the frontend oauth2 cookies is deleted');
        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user', $responseData['cookieData'], 'assert: no frontend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertEquals($expectedRedirectUri, (string)$redirectUri, 'assert: we are redirected to the TYPO3 frontend');
        self::assertEquals(
            'OAuth2: Done. Redirection to original requested location',
            $responseData['response']->getReasonPhrase(),
            'assert: response redirect reason is set'
        );
        self::assertEquals(302, $responseData['response']->getStatusCode(), 'assert: response redirect code is set');

        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest((string)$redirectUri, $responseData['cookieData']));
        $responseData = $this->goToLoginPage($siteBaseUri, $languageSlug, $responseData);
        self::assertStringContainsString('Login CE', $responseData['pageMarkup'], 'assert: we are on the login page');
        self::assertStringNotContainsString('type="submit" value="Logout"', $responseData['pageMarkup'], 'assert: we are not logged in');
    }

    private function goToLoginPage(string $siteBaseUri, string $languageSlug, array $responseData = []): array
    {
        $uri = $siteBaseUri . $languageSlug . '/login';
        return $this->fetchFrontendPageContens($this->buildGetRequest($uri, $responseData['cookieData'] ?? []));
    }
}
