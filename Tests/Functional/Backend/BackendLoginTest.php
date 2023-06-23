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

class BackendLoginTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function assertThatABackendUserIsAbleToLoginWithAOAuth2ProviderWhichConfiguredToBeUsedWithinTheBackendAndActivatedByTheUser(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(100, 1, 'gitlab2-be', 'user1-gitlab2-be-remote-identity');

        // Click "login with gitlab2-be"
        $responseData = $this->loginIntoBackendWithOAuth2Provider(self::SITE1_BASE_URI, 'gitlab2-be');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $callbackUri = $authorizationQuery['redirect_uri'];
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertEquals('gitlab2', $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals(
            'http://localhost/typo3/login?loginProvider=1616569531&oauth2-provider=gitlab2-be&login_status=login&commandLI=attempt',
            $authorizationQuery['redirect_uri'] ?? null,
            'assert: the correct callback uri is sent'
        );
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(1, $oauth2BackendSessionData, 'assert: 1 oauth2 backend session was created');
        self::assertEquals(0, (int)$oauth2BackendSessionData[0]['ses_userid'], 'assert: the backend session is not connected to a user');
        self::assertEquals(['oauth2-state' => $requestedState], $oauth2BackendSessionData[0]['ses_data'], 'assert: the backend session contains the oauth2 state, which is also contained in the authorization request uri');

        // Simulate the redirect from the remote instance back to the TYPO3
        $requestedCallbackUri = new Uri(urldecode($authorizationQuery['redirect_uri']));
        parse_str($requestedCallbackUri->getQuery(), $respondedCallbackQuery);
        $respondedCallbackQuery['state'] = $requestedState;
        $respondedCallbackQuery['code'] = 'some-remote-api-access-code';
        $respondedCallbackUri = (string)$requestedCallbackUri->withQuery(http_build_query($respondedCallbackQuery));

        // At this point we were redirected back from the remote instance to the TYPO3
        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part
        $responseData = $this->fetchBackendPageContens(
            $this->buildGetRequest($respondedCallbackUri, $responseData['cookieData']),
            true,
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

        // @todo: should not be present
        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the backend oauth2 cookie exists');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertArrayHasKey('be_typo_user', $responseData['cookieData'], 'assert: the backend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        // @todo: session data is empty but the record should be removed completely
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertStringContainsString('toolbar-item-avatar', $responseData['pageMarkup'], 'assert: we are logged in');
    }

    /**
     * @test
     */
    public function assertThatABackendUserIsUnableToLoginWithAOAuth2ProviderWhichIsNotConfiguredToBeUsedWithinTheBackendButActivatedByTheUser(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(100, 1, 'gitlab1-fe', 'user1-gitlab1-fe-remote-identity');

        // Click "login with gitlab1-fe"
        $responseData = $this->loginIntoBackendWithOAuth2Provider(self::SITE1_BASE_URI, 'gitlab1-fe');

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayNotHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 backend cookie exists');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user', $responseData['cookieData'], 'assert: no backend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertStringNotContainsString('toolbar-item-avatar', $responseData['pageMarkup'], 'assert: we are logged in');
    }

    /**
     * @test
     */
    public function assertThatABackendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendButNotActivatedByTheUser(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(100, 1, 'gitlab2-be', 'user1-gitlab2-be-remote-identity');

        // Click "login with gitlab3-both"
        $responseData = $this->loginIntoBackendWithOAuth2Provider(self::SITE1_BASE_URI, 'gitlab3-both');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $callbackUri = $authorizationQuery['redirect_uri'];
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertEquals('gitlab3', $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals('cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc', $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals(
            'http://localhost/typo3/login?loginProvider=1616569531&oauth2-provider=gitlab3-both&login_status=login&commandLI=attempt',
            $authorizationQuery['redirect_uri'] ?? null,
            'assert: the correct callback uri is sent'
        );
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(1, $oauth2BackendSessionData, 'assert: 1 oauth2 backend session was created');
        self::assertEquals(0, (int)$oauth2BackendSessionData[0]['ses_userid'], 'assert: the backend session is not connected to a user');
        self::assertEquals(['oauth2-state' => $requestedState], $oauth2BackendSessionData[0]['ses_data'], 'assert: the backend session contains the oauth2 state, which is also contained in the authorization request uri');

        // Simulate the redirect from the remote instance back to the TYPO3
        $requestedCallbackUri = new Uri(urldecode($authorizationQuery['redirect_uri']));
        parse_str($requestedCallbackUri->getQuery(), $respondedCallbackQuery);
        $respondedCallbackQuery['state'] = $requestedState;
        $respondedCallbackQuery['code'] = 'some-remote-api-access-code';
        $respondedCallbackUri = (string)$requestedCallbackUri->withQuery(http_build_query($respondedCallbackQuery));

        // At this point we were redirected back from the remote instance to the TYPO3
        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part
        $responseData = $this->fetchBackendPageContens(
            $this->buildGetRequest($respondedCallbackUri, $responseData['cookieData']),
            true,
            $this->buildRequestContext(['X_TYPO3_TESTING_FRAMEWORK' => ['HTTP' => ['mocks' => [
                // successful Oauth2Service::getUser() request
                'className' => SuccessfulGetUserFromGitLabHttpMock::class,
                'options' => [
                    'remoteUser' => [
                        'id' => 'user1-gitlab3-both-remote-identity',
                        'username' => 'user1-gitlab3-both',
                        'name' => 'user1 gitlab3-both',
                        'email' => 'user1-gitlab3-both@waldhacker.dev',
                    ],
                ],
            ]]]])
        );

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        // @todo: should not be present
        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the backend oauth2 cookie exists');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user', $responseData['cookieData'], 'assert: no backend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        // @todo: session data is empty but the record should be removed completely
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertStringNotContainsString('toolbar-item-avatar', $responseData['pageMarkup'], 'assert: we are logged in');
    }

    /**
     * @test
     */
    public function assertThatABackendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendButActivatedBy2TYPO3Users(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(100, 1, 'gitlab3-both', 'user1-gitlab3-both-remote-identity');
        $this->createBackendUserOauth2ProviderConfiguration(101, 2, 'gitlab3-both', 'user1-gitlab3-both-remote-identity');

        // Click "login with gitlab3-both"
        $responseData = $this->loginIntoBackendWithOAuth2Provider(self::SITE1_BASE_URI, 'gitlab3-both');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $callbackUri = $authorizationQuery['redirect_uri'];
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertEquals('gitlab3', $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals('cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc', $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals(
            'http://localhost/typo3/login?loginProvider=1616569531&oauth2-provider=gitlab3-both&login_status=login&commandLI=attempt',
            $authorizationQuery['redirect_uri'] ?? null,
            'assert: the correct callback uri is sent'
        );
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(1, $oauth2BackendSessionData, 'assert: 1 oauth2 backend session was created');
        self::assertEquals(0, (int)$oauth2BackendSessionData[0]['ses_userid'], 'assert: the backend session is not connected to a user');
        self::assertEquals(['oauth2-state' => $requestedState], $oauth2BackendSessionData[0]['ses_data'], 'assert: the backend session contains the oauth2 state, which is also contained in the authorization request uri');

        // Simulate the redirect from the remote instance back to the TYPO3
        $requestedCallbackUri = new Uri(urldecode($authorizationQuery['redirect_uri']));
        parse_str($requestedCallbackUri->getQuery(), $respondedCallbackQuery);
        $respondedCallbackQuery['state'] = $requestedState;
        $respondedCallbackQuery['code'] = 'some-remote-api-access-code';
        $respondedCallbackUri = (string)$requestedCallbackUri->withQuery(http_build_query($respondedCallbackQuery));

        // At this point we were redirected back from the remote instance to the TYPO3
        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part
        $responseData = $this->fetchBackendPageContens(
            $this->buildGetRequest($respondedCallbackUri, $responseData['cookieData']),
            true,
            $this->buildRequestContext(['X_TYPO3_TESTING_FRAMEWORK' => ['HTTP' => ['mocks' => [
                // successful Oauth2Service::getUser() request
                'className' => SuccessfulGetUserFromGitLabHttpMock::class,
                'options' => [
                    'remoteUser' => [
                        'id' => 'user1-gitlab3-both-remote-identity',
                        'username' => 'user1-gitlab3-both',
                        'name' => 'user1 gitlab3-both',
                        'email' => 'user1-gitlab3-both@waldhacker.dev',
                    ],
                ],
            ]]]])
        );

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        // @todo: should not be present
        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the backend oauth2 cookie exists');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user', $responseData['cookieData'], 'assert: no backend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        // @todo: session data is empty but the record should be removed completely
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertStringNotContainsString('toolbar-item-avatar', $responseData['pageMarkup'], 'assert: we are logged in');
    }

    /**
     * @test
     */
    public function assertThatABackendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendButActivated2Times(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(100, 1, 'gitlab3-both', 'user1-gitlab3-both-remote-identity');
        $this->createBackendUserOauth2ProviderConfiguration(101, 1, 'gitlab3-both', 'user1-gitlab3-both-remote-identity');

        // Click "login with gitlab3-both"
        $responseData = $this->loginIntoBackendWithOAuth2Provider(self::SITE1_BASE_URI, 'gitlab3-both');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $callbackUri = $authorizationQuery['redirect_uri'];
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertEquals('gitlab3', $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals('cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc', $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals(
            'http://localhost/typo3/login?loginProvider=1616569531&oauth2-provider=gitlab3-both&login_status=login&commandLI=attempt',
            $authorizationQuery['redirect_uri'] ?? null,
            'assert: the correct callback uri is sent'
        );
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(1, $oauth2BackendSessionData, 'assert: 1 oauth2 backend session was created');
        self::assertEquals(0, (int)$oauth2BackendSessionData[0]['ses_userid'], 'assert: the backend session is not connected to a user');
        self::assertEquals(['oauth2-state' => $requestedState], $oauth2BackendSessionData[0]['ses_data'], 'assert: the backend session contains the oauth2 state, which is also contained in the authorization request uri');

        // Simulate the redirect from the remote instance back to the TYPO3
        $requestedCallbackUri = new Uri(urldecode($authorizationQuery['redirect_uri']));
        parse_str($requestedCallbackUri->getQuery(), $respondedCallbackQuery);
        $respondedCallbackQuery['state'] = $requestedState;
        $respondedCallbackQuery['code'] = 'some-remote-api-access-code';
        $respondedCallbackUri = (string)$requestedCallbackUri->withQuery(http_build_query($respondedCallbackQuery));

        // At this point we were redirected back from the remote instance to the TYPO3
        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part
        $responseData = $this->fetchBackendPageContens(
            $this->buildGetRequest($respondedCallbackUri, $responseData['cookieData']),
            true,
            $this->buildRequestContext(['X_TYPO3_TESTING_FRAMEWORK' => ['HTTP' => ['mocks' => [
                // successful Oauth2Service::getUser() request
                'className' => SuccessfulGetUserFromGitLabHttpMock::class,
                'options' => [
                    'remoteUser' => [
                        'id' => 'user1-gitlab3-both-remote-identity',
                        'username' => 'user1-gitlab3-both',
                        'name' => 'user1 gitlab3-both',
                        'email' => 'user1-gitlab3-both@waldhacker.dev',
                    ],
                ],
            ]]]])
        );

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        // @todo: should not be present
        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the backend oauth2 cookie exists');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user', $responseData['cookieData'], 'assert: no backend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        // @todo: session data is empty but the record should be removed completely
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertStringNotContainsString('toolbar-item-avatar', $responseData['pageMarkup'], 'assert: we are logged in');
    }

    /**
     * @test
     */
    public function assertThatABackendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendButActivationIsInvalid(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(100, 1, 'gitlab3-both', '_invalid_');

        // Click "login with gitlab3-both"
        $responseData = $this->loginIntoBackendWithOAuth2Provider(self::SITE1_BASE_URI, 'gitlab3-both');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $callbackUri = $authorizationQuery['redirect_uri'];
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertEquals('gitlab3', $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals('cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc', $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals(
            'http://localhost/typo3/login?loginProvider=1616569531&oauth2-provider=gitlab3-both&login_status=login&commandLI=attempt',
            $authorizationQuery['redirect_uri'] ?? null,
            'assert: the correct callback uri is sent'
        );
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(1, $oauth2BackendSessionData, 'assert: 1 oauth2 backend session was created');
        self::assertEquals(0, (int)$oauth2BackendSessionData[0]['ses_userid'], 'assert: the backend session is not connected to a user');
        self::assertEquals(['oauth2-state' => $requestedState], $oauth2BackendSessionData[0]['ses_data'], 'assert: the backend session contains the oauth2 state, which is also contained in the authorization request uri');

        // Simulate the redirect from the remote instance back to the TYPO3
        $requestedCallbackUri = new Uri(urldecode($authorizationQuery['redirect_uri']));
        parse_str($requestedCallbackUri->getQuery(), $respondedCallbackQuery);
        $respondedCallbackQuery['state'] = $requestedState;
        $respondedCallbackQuery['code'] = 'some-remote-api-access-code';
        $respondedCallbackUri = (string)$requestedCallbackUri->withQuery(http_build_query($respondedCallbackQuery));

        // At this point we were redirected back from the remote instance to the TYPO3
        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part
        $responseData = $this->fetchBackendPageContens(
            $this->buildGetRequest($respondedCallbackUri, $responseData['cookieData']),
            true,
            $this->buildRequestContext(['X_TYPO3_TESTING_FRAMEWORK' => ['HTTP' => ['mocks' => [
                // successful Oauth2Service::getUser() request
                'className' => SuccessfulGetUserFromGitLabHttpMock::class,
                'options' => [
                    'remoteUser' => [
                        'id' => '_invalid_',
                        'username' => 'user1-gitlab3-both',
                        'name' => 'user1 gitlab3-both',
                        'email' => 'user1-gitlab3-both@waldhacker.dev',
                    ],
                ],
            ]]]])
        );

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        // @todo: should not be present
        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the backend oauth2 cookie exists');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user', $responseData['cookieData'], 'assert: no backend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        // @todo: session data is empty but the record should be removed completely
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertStringNotContainsString('toolbar-item-avatar', $responseData['pageMarkup'], 'assert: we are logged in');
    }

    public function assertThatABackendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendIfInvalidaDataIsSumbittedDataProvider(): \Generator
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
                'oauth2-provider' => 'gitlab2-be',
                'oauth2-state' => '',
                'oauth2-code' => 'some-remote-api-access-code',
            ],
            'clearSession' => false,
        ];

        yield 'empty code' => [
            'remoteRequestData' => [
                'oauth2-provider' => 'gitlab2-be',
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
                'oauth2-provider' => 'gitlab2-be',
                'oauth2-state' => '_state',
                'oauth2-code' => '_invalid',
            ],
            'clearSession' => false,
        ];

        yield 'invalid session data' => [
            'remoteRequestData' => [
                'oauth2-provider' => 'gitlab2-be',
                'oauth2-state' => '_state',
                'oauth2-code' => 'some-remote-api-access-code',
            ],
            'clearSession' => true,
        ];
    }

    /**
     * @test
     * @dataProvider assertThatABackendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendIfInvalidaDataIsSumbittedDataProvider
     */
    public function assertThatABackendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendIfInvalidaDataIsSumbitted(array $remoteRequestData, bool $clearSession): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(100, 1, 'gitlab2-be', 'user1-gitlab2-be-remote-identity');

        // Click "login with gitlab2-be"
        $responseData = $this->loginIntoBackendWithOAuth2Provider(self::SITE1_BASE_URI, 'gitlab2-be');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $callbackUri = $authorizationQuery['redirect_uri'];
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertEquals('gitlab2', $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals(
            'http://localhost/typo3/login?loginProvider=1616569531&oauth2-provider=gitlab2-be&login_status=login&commandLI=attempt',
            $authorizationQuery['redirect_uri'] ?? null,
            'assert: the correct callback uri is sent'
        );
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(1, $oauth2BackendSessionData, 'assert: 1 oauth2 backend session was created');
        self::assertEquals(0, (int)$oauth2BackendSessionData[0]['ses_userid'], 'assert: the backend session is not connected to a user');
        self::assertEquals(['oauth2-state' => $requestedState], $oauth2BackendSessionData[0]['ses_data'], 'assert: the backend session contains the oauth2 state, which is also contained in the authorization request uri');

        // Simulate the redirect from the remote instance back to the TYPO3
        $requestedCallbackUri = new Uri(urldecode($authorizationQuery['redirect_uri']));
        parse_str($requestedCallbackUri->getQuery(), $respondedCallbackQuery);
        $respondedCallbackQuery['oauth2-provider'] = $remoteRequestData['oauth2-provider'];
        $respondedCallbackQuery['state'] = $remoteRequestData['oauth2-state'] === '_state' ? $requestedState : $remoteRequestData['oauth2-state'];
        $respondedCallbackQuery['code'] = $remoteRequestData['oauth2-code'];

        if ($clearSession) {
            $this->removeOauth2BackendSessionData();
        }

        $respondedCallbackUri = (string)$requestedCallbackUri->withQuery(http_build_query($respondedCallbackQuery));

        // At this point we were redirected back from the remote instance to the TYPO3
        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part
        $responseData = $this->fetchBackendPageContens(
            $this->buildGetRequest($respondedCallbackUri, $responseData['cookieData']),
            true,
            $this->buildRequestContext(['X_TYPO3_TESTING_FRAMEWORK' => ['HTTP' => ['mocks' => [
                'className' => $remoteRequestData['oauth2-code'] === '_invalid' ? InvalidAccessTokenResponseFromGitLabHttpMock::class : SuccessfulGetUserFromGitLabHttpMock::class,
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

        // @todo: should not be present
        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the backend oauth2 cookie exists');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user', $responseData['cookieData'], 'assert: no backend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no oauth2 backend session exists');
        self::assertStringNotContainsString('toolbar-item-avatar', $responseData['pageMarkup'], 'assert: we are logged in');
    }

    public function assertThatABackendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendAndActivatedButTheTYPO3UserIsInactiveDataProvider(): \Generator
    {
        yield 'inactive user' => [
            'userUid' => 7,
        ];

        yield 'deleted user' => [
            'userUid' => 8,
            'deleteUser' => true
        ];
    }

    /**
     * @test
     * @dataProvider assertThatABackendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendAndActivatedButTheTYPO3UserIsInactiveDataProvider
     */
    public function assertThatABackendUserIsUnableToLoginWithAOAuth2ProviderWhichIsConfiguredToBeUsedWithinTheBackendAndActivatedButTheTYPO3UserIsInactive(int $userUid, bool $deleteUser = false): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(100, $userUid, 'gitlab3-both', 'user1-gitlab3-both-remote-identity');
        if ($deleteUser) {
            $this->deleteBackendUser($userUid);
        }

        // Click "login with gitlab3-both"
        $responseData = $this->loginIntoBackendWithOAuth2Provider(self::SITE1_BASE_URI, 'gitlab3-both');

        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getAuthorizationUrl() part
        $authorizationRedirectUri = new Uri($responseData['response']->getHeaderLine('location'));
        parse_str($authorizationRedirectUri->getQuery(), $authorizationQuery);
        $requestedState = $authorizationQuery['state'] ?? null;
        $callbackUri = $authorizationQuery['redirect_uri'];
        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the oauth2 backend cookie is sent to the client');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertEquals('gitlab3', $authorizationRedirectUri->getHost(), 'assert: the correct remote instance is called');
        self::assertEquals('code', $authorizationQuery['response_type'] ?? null, 'assert: the correct response type is requested');
        self::assertEquals('cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc', $authorizationQuery['client_id'] ?? null, 'assert: the correct client id is requested');
        self::assertEquals(
            'http://localhost/typo3/login?loginProvider=1616569531&oauth2-provider=gitlab3-both&login_status=login&commandLI=attempt',
            $authorizationQuery['redirect_uri'] ?? null,
            'assert: the correct callback uri is sent'
        );
        self::assertNotEmpty($requestedState, 'assert: an oauth2 state was created and set in the authorization request uri');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        self::assertCount(1, $oauth2BackendSessionData, 'assert: 1 oauth2 backend session was created');
        self::assertEquals(0, (int)$oauth2BackendSessionData[0]['ses_userid'], 'assert: the backend session is not connected to a user');
        self::assertEquals(['oauth2-state' => $requestedState], $oauth2BackendSessionData[0]['ses_data'], 'assert: the backend session contains the oauth2 state, which is also contained in the authorization request uri');

        // Simulate the redirect from the remote instance back to the TYPO3
        $requestedCallbackUri = new Uri(urldecode($authorizationQuery['redirect_uri']));
        parse_str($requestedCallbackUri->getQuery(), $respondedCallbackQuery);
        $respondedCallbackQuery['state'] = $requestedState;
        $respondedCallbackQuery['code'] = 'some-remote-api-access-code';
        $respondedCallbackUri = (string)$requestedCallbackUri->withQuery(http_build_query($respondedCallbackQuery));

        // At this point we were redirected back from the remote instance to the TYPO3
        // This is the Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() part
        $responseData = $this->fetchBackendPageContens(
            $this->buildGetRequest($respondedCallbackUri, $responseData['cookieData']),
            true,
            $this->buildRequestContext(['X_TYPO3_TESTING_FRAMEWORK' => ['HTTP' => ['mocks' => [
                // successful Oauth2Service::getUser() request
                'className' => SuccessfulGetUserFromGitLabHttpMock::class,
                'options' => [
                    'remoteUser' => [
                        'id' => 'user1-gitlab3-both-remote-identity',
                        'username' => 'user1-gitlab3-both',
                        'name' => 'user1 gitlab3-both',
                        'email' => 'user1-gitlab3-both@waldhacker.dev',
                    ],
                ],
            ]]]])
        );

        $oauth2BackendSessionData = $this->getOauth2BackendSessionData();
        $oauth2FrontendSessionData = $this->getOauth2FrontendSessionData();

        // @todo: should not be present
        self::assertArrayHasKey('be_typo_user_oauth2', $responseData['cookieData'], 'assert: the backend oauth2 cookie exists');
        self::assertArrayNotHasKey('fe_typo_user_oauth2', $responseData['cookieData'], 'assert: no oauth2 frontend cookie is sent to the client');
        self::assertArrayNotHasKey('be_typo_user', $responseData['cookieData'], 'assert: no backend user cookie exists');
        self::assertCount(0, $oauth2FrontendSessionData, 'assert: no oauth2 frontend session exists');
        // @todo: session data is empty but the record should be removed completely
        self::assertCount(0, $oauth2BackendSessionData, 'assert: no backend backend session exists');
        self::assertStringNotContainsString('toolbar-item-avatar', $responseData['pageMarkup'], 'assert: we are logged in');
    }

    private function loginIntoBackendWithOAuth2Provider(string $siteBaseUri, string $providerId): array
    {
        $uri = $siteBaseUri . '/typo3/login?loginProvider=1616569532';

        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($uri));
        $loginFormData = (new DataPusher(new DataExtractor($responseData['pageMarkup'])))
            ->with('oauth2-provider', $providerId);
        return $this->fetchBackendPageContens($loginFormData->toPostRequest($this->buildGetRequest()), false);
    }
}
