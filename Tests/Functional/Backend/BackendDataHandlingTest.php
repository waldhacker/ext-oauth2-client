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

use Waldhacker\Oauth2Client\Tests\Functional\Framework\FunctionalTestCase;

class BackendDataHandlingTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function assertThatAnAdminBackendUserIsAbleToOnlyCreateOAuth2BackendConfigurationsForHisOwnViaRecordCommitEndpoint(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'admin', 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "create be_config with ..." links
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-be-1', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-be-2', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-be-3', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-be-4', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-be-5', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-be-6', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-be-7', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertCount(7, $backendUserOauth2ProviderConfigurations, 'assert: 7 oauth2 provider configuration exists for backend users');

        self::assertEquals(0, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration (1) is valid');
        self::assertEquals('_invalid_', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration (1) is valid');
        self::assertEquals('_invalid_', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration (1) is valid');

        self::assertEquals(0, (int)$backendUserOauth2ProviderConfigurations[1]['parentid'], 'assert: backend user oauth2 provider configuration (2) is valid');
        self::assertEquals('gitlab', $backendUserOauth2ProviderConfigurations[1]['provider'], 'assert: backend user oauth2 provider configuration (2) is valid');
        self::assertEquals('new1', $backendUserOauth2ProviderConfigurations[1]['identifier'], 'assert: backend user oauth2 provider configuration (2) is valid');

        self::assertEquals(0, (int)$backendUserOauth2ProviderConfigurations[2]['parentid'], 'assert: backend user oauth2 provider configuration (3) is valid');
        self::assertEquals('gitlab', $backendUserOauth2ProviderConfigurations[2]['provider'], 'assert: backend user oauth2 provider configuration (3) is valid');
        self::assertEquals('new2', $backendUserOauth2ProviderConfigurations[2]['identifier'], 'assert: backend user oauth2 provider configuration (3) is valid');

        self::assertEquals(1, (int)$backendUserOauth2ProviderConfigurations[3]['parentid'], 'assert: backend user oauth2 provider configuration (4) is valid');
        self::assertEquals('gitlab', $backendUserOauth2ProviderConfigurations[3]['provider'], 'assert: backend user oauth2 provider configuration (4) is valid');
        self::assertEquals('new3', $backendUserOauth2ProviderConfigurations[3]['identifier'], 'assert: backend user oauth2 provider configuration (4) is valid');

        self::assertEquals(0, (int)$backendUserOauth2ProviderConfigurations[4]['parentid'], 'assert: backend user oauth2 provider configuration (5) is valid');
        self::assertEquals('_invalid_', $backendUserOauth2ProviderConfigurations[4]['provider'], 'assert: backend user oauth2 provider configuration (5) is valid');
        self::assertEquals('_invalid_', $backendUserOauth2ProviderConfigurations[4]['identifier'], 'assert: backend user oauth2 provider configuration (5) is valid');

        self::assertEquals(0, (int)$backendUserOauth2ProviderConfigurations[5]['parentid'], 'assert: backend user oauth2 provider configuration (6) is valid');
        self::assertEquals('gitlab', $backendUserOauth2ProviderConfigurations[5]['provider'], 'assert: backend user oauth2 provider configuration (6) is valid');
        self::assertEquals('new4', $backendUserOauth2ProviderConfigurations[5]['identifier'], 'assert: backend user oauth2 provider configuration (6) is valid');

        // @todo: even admins should not be able do this
        self::assertEquals(3, (int)$backendUserOauth2ProviderConfigurations[6]['parentid'], 'assert: backend user oauth2 provider configuration (7) is valid');
        self::assertEquals('gitlab', $backendUserOauth2ProviderConfigurations[6]['provider'], 'assert: backend user oauth2 provider configuration (7) is valid');
        self::assertEquals('new5', $backendUserOauth2ProviderConfigurations[6]['identifier'], 'assert: backend user oauth2 provider configuration (7) is valid');
    }

    /**
     * @test
     */
    public function assertThatABackendUserIsUnableToCreateOAuth2BackendConfigurationsViaRecordCommitEndpoint(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'user3', 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "create be_config with ..." links
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-be-1', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-be-2', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-be-3', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-be-4', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-be-5', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-be-6', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-be-7', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertCount(7, $backendUserOauth2ProviderConfigurations, 'assert: 7 oauth2 provider configuration exists for backend users');

        self::assertEquals(0, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration (1) is valid');
        self::assertEquals('_invalid_', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration (1) is valid');
        self::assertEquals('_invalid_', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration (1) is valid');

        self::assertEquals(0, (int)$backendUserOauth2ProviderConfigurations[1]['parentid'], 'assert: backend user oauth2 provider configuration (2) is valid');
        self::assertEquals('gitlab', $backendUserOauth2ProviderConfigurations[1]['provider'], 'assert: backend user oauth2 provider configuration (2) is valid');
        self::assertEquals('new1', $backendUserOauth2ProviderConfigurations[1]['identifier'], 'assert: backend user oauth2 provider configuration (2) is valid');

        self::assertEquals(0, (int)$backendUserOauth2ProviderConfigurations[2]['parentid'], 'assert: backend user oauth2 provider configuration (3) is valid');
        self::assertEquals('gitlab', $backendUserOauth2ProviderConfigurations[2]['provider'], 'assert: backend user oauth2 provider configuration (3) is valid');
        self::assertEquals('new2', $backendUserOauth2ProviderConfigurations[2]['identifier'], 'assert: backend user oauth2 provider configuration (3) is valid');

        self::assertEquals(0, (int)$backendUserOauth2ProviderConfigurations[3]['parentid'], 'assert: backend user oauth2 provider configuration (4) is valid');
        self::assertEquals('gitlab', $backendUserOauth2ProviderConfigurations[3]['provider'], 'assert: backend user oauth2 provider configuration (4) is valid');
        self::assertEquals('new3', $backendUserOauth2ProviderConfigurations[3]['identifier'], 'assert: backend user oauth2 provider configuration (4) is valid');

        self::assertEquals(0, (int)$backendUserOauth2ProviderConfigurations[4]['parentid'], 'assert: backend user oauth2 provider configuration (5) is valid');
        self::assertEquals('_invalid_', $backendUserOauth2ProviderConfigurations[4]['provider'], 'assert: backend user oauth2 provider configuration (5) is valid');
        self::assertEquals('_invalid_', $backendUserOauth2ProviderConfigurations[4]['identifier'], 'assert: backend user oauth2 provider configuration (5) is valid');

        self::assertEquals(0, (int)$backendUserOauth2ProviderConfigurations[5]['parentid'], 'assert: backend user oauth2 provider configuration (6) is valid');
        self::assertEquals('gitlab', $backendUserOauth2ProviderConfigurations[5]['provider'], 'assert: backend user oauth2 provider configuration (6) is valid');
        self::assertEquals('new4', $backendUserOauth2ProviderConfigurations[5]['identifier'], 'assert: backend user oauth2 provider configuration (6) is valid');

        self::assertEquals(0, (int)$backendUserOauth2ProviderConfigurations[6]['parentid'], 'assert: backend user oauth2 provider configuration (7) is valid');
        self::assertEquals('gitlab', $backendUserOauth2ProviderConfigurations[6]['provider'], 'assert: backend user oauth2 provider configuration (7) is valid');
        self::assertEquals('new5', $backendUserOauth2ProviderConfigurations[6]['identifier'], 'assert: backend user oauth2 provider configuration (7) is valid');
    }

    public function assertThatABackendUserIsUnableToCreateOAuth2BackendConfigurationsViaRecordCommitEndpointV10DataProvider(): \Generator
    {
        yield 'oauth2test-create-be-1' => [
            'oauth2test-create-be-1',
        ];

        yield 'oauth2test-create-be-2' => [
            'oauth2test-create-be-2',
        ];

        yield 'oauth2test-create-be-3' => [
            'oauth2test-create-be-3',
        ];

        yield 'oauth2test-create-be-4' => [
            'oauth2test-create-be-4',
        ];

        yield 'oauth2test-create-be-5' => [
            'oauth2test-create-be-5',
        ];

        yield 'oauth2test-create-be-6' => [
            'oauth2test-create-be-6',
        ];

        yield 'oauth2test-create-be-7' => [
            'oauth2test-create-be-7',
        ];
    }

    /**
     * @test
     * @dataProvider assertThatABackendUserIsUnableToCreateOAuth2BackendConfigurationsViaRecordCommitEndpointV10DataProvider
     */
    public function assertThatABackendUserIsUnableToCreateOAuth2BackendConfigurationsViaRecordCommitEndpointV10(string $oauth2DeactivationId): void
    {
        self::expectException(\TypeError::class);

        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'user3', 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "create be_config with ..." link
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData($oauth2DeactivationId, $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
    }

    /**
     * @test
     */
    public function assertThatAnAdminBackendUserIsAbleToDeactivateOAuth2BackendConfigurationsViaRecordCommitEndpoint(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(1, 1, 'gitlab2-be', 'user1-gitlab2-be-remote-identity');
        $this->createBackendUserOauth2ProviderConfiguration(2, 2, 'gitlab2-be', 'user2-gitlab2-be-remote-identity');

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'admin', 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "deactivate be_config=1" link
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-deactivate-be-1', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertCount(1, $backendUserOauth2ProviderConfigurations, 'assert: 1 oauth2 provider configuration exists for backend users');
        self::assertEquals(2, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "deactivate be_config=2" link
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-deactivate-be-2', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertCount(0, $backendUserOauth2ProviderConfigurations, 'assert: no oauth2 provider configuration exists for backend users');
    }

    /**
     * @test
     */
    public function assertThatABackendUserIsAbleToOnlyDeactivateOAuth2BackendConfigurationsWhichHeOwnsViaRecordCommitEndpoint(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(1, 1, 'gitlab2-be', 'user1-gitlab2-be-remote-identity');
        $this->createBackendUserOauth2ProviderConfiguration(2, 2, 'gitlab2-be', 'user2-gitlab2-be-remote-identity');

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'user2', 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "deactivate be_config=2" link
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-deactivate-be-2', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertCount(1, $backendUserOauth2ProviderConfigurations, 'assert: 1 oauth2 provider configuration exists for backend users');
        self::assertEquals(1, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "deactivate be_config=1" link
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-deactivate-be-1', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertCount(1, $backendUserOauth2ProviderConfigurations, 'assert: 1 oauth2 provider configuration exists for backend users');
        self::assertEquals(1, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
    }

    /**
     * @test
     */
    public function assertThatAnAdminBackendUserIsUnableToOverrideOAuth2BackendConfigurationsViaRecordCommitEndpoint(): void
    {
        $this->resetSessionData();

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'admin', 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "override parentid=1 for be_config=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(1, 3, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-1', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertEquals(3, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "override provider=gitlab, identifier=existing1 for be_config=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(1, 3, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-2', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertEquals(3, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "override provider=gitlab, identifier=existing2, parentid=1 for be_config=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(1, 3, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-3', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertEquals(3, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "override provider=gitlab, identifier=existing4, parentid=1 for be_config=1 and be_user=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(1, 3, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-4', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        // @todo: even admins should not be able do this
        self::assertEquals(1, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "override parentid=3 for be_config=2" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(2, 1, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-5', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertEquals(1, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "override provider=gitlab, identifier=existing4 for be_config=2" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(2, 1, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-6', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertEquals(1, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "override provider=gitlab, identifier=existing5, parentid=3 for be_config=2" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(2, 1, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-7', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertEquals(1, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "override provider=gitlab, identifier=existing5, parentid=3 for be_config=2 and be_user=3" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(2, 1, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-8', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        // @todo: even admins should not be able do this
        self::assertEquals(3, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');
    }

    /**
     * @test
     */
    public function assertThatABackendUserIsUnableToOverrideOAuth2BackendConfigurationsViaRecordCommitEndpoint(): void
    {
        $this->resetSessionData();

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'user2', 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "override parentid=1 for be_config=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(1, 3, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-1', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertEquals(3, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "override provider=gitlab, identifier=existing1 for be_config=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(1, 3, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-2', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertEquals(3, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "override provider=gitlab, identifier=existing2, parentid=1 for be_config=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(1, 3, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-3', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertEquals(3, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "override provider=gitlab, identifier=existing4, parentid=1 for be_config=1 and be_user=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(1, 3, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-4', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertEquals(3, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "override parentid=3 for be_config=2" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(2, 1, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-5', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertEquals(1, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "override provider=gitlab, identifier=existing4 for be_config=2" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(2, 1, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-6', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertEquals(1, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "override provider=gitlab, identifier=existing5, parentid=3 for be_config=2" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(2, 1, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-7', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertEquals(1, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');

        // Click "override provider=gitlab, identifier=existing5, parentid=3 for be_config=2 and be_user=3" link
        $this->resetOauth2ProviderConfigurations();
        $this->createBackendUserOauth2ProviderConfiguration(2, 1, 'gitlab2-be', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-be-8', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $backendUserOauth2ProviderConfigurations = $this->getBackendUserOauth2ProviderConfigurations();
        self::assertEquals(1, (int)$backendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('gitlab2-be', $backendUserOauth2ProviderConfigurations[0]['provider'], 'assert: backend user oauth2 provider configuration is valid');
        self::assertEquals('remote-id', $backendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: backend user oauth2 provider configuration is valid');
    }

    /**
     * @test
     */
    public function assertThatAnAdminBackendUserIsUnableToCreateOAuth2FrontendConfigurations(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'admin', 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "create fe_config ..." links
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-fe-1', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-fe-2', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-fe-3', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-fe-4', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertCount(4, $frontendUserOauth2ProviderConfigurations, 'assert: 4 oauth2 provider configuration exists for frontend users');

        self::assertEquals(0, (int)$frontendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: frontend user oauth2 provider configuration (1) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[0]['provider'], 'assert: frontend user oauth2 provider configuration (1) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: frontend user oauth2 provider configuration (1) is valid');

        self::assertEquals(0, (int)$frontendUserOauth2ProviderConfigurations[1]['parentid'], 'assert: frontend user oauth2 provider configuration (2) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[1]['provider'], 'assert: frontend user oauth2 provider configuration (2) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[1]['identifier'], 'assert: frontend user oauth2 provider configuration (2) is valid');

        self::assertEquals(0, (int)$frontendUserOauth2ProviderConfigurations[2]['parentid'], 'assert: frontend user oauth2 provider configuration (3) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[2]['provider'], 'assert: frontend user oauth2 provider configuration (3) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[2]['identifier'], 'assert: frontend user oauth2 provider configuration (3) is valid');

        self::assertEquals(0, (int)$frontendUserOauth2ProviderConfigurations[3]['parentid'], 'assert: frontend user oauth2 provider configuration (4) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[3]['provider'], 'assert: frontend user oauth2 provider configuration (4) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[3]['identifier'], 'assert: frontend user oauth2 provider configuration (4) is valid');
    }

    public function assertThatABackendUserWithoutFeUserEditRightsIsUnableToCreateOAuth2FrontendConfigurationsDataProvider(): \Generator
    {
        yield 'user2' => [
            'userName' => 'user2',
        ];

        yield 'user3' => [
            'userName' => 'user3',
        ];
    }

    /**
     * @test
     * @dataProvider assertThatABackendUserWithoutFeUserEditRightsIsUnableToCreateOAuth2FrontendConfigurationsDataProvider
     */
    public function assertThatABackendUserWithoutFeUserEditRightsIsUnableToCreateOAuth2FrontendConfigurations(string $userName): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, $userName, 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "create fe_config ..." links
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-fe-1', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-fe-2', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-fe-3', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-fe-4', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertCount(0, $frontendUserOauth2ProviderConfigurations, 'assert: 0 oauth2 provider configuration exists for frontend users');
    }

    public function assertThatABackendUserWithFeUserEditRightsIsUnableToCreateOAuth2FrontendConfigurationsDataProvider(): \Generator
    {
        yield 'user4' => [
            'userName' => 'user4',
        ];

        yield 'user5' => [
            'userName' => 'user5',
        ];
    }

    /**
     * @test
     * @dataProvider assertThatABackendUserWithFeUserEditRightsIsUnableToCreateOAuth2FrontendConfigurationsDataProvider
     */
    public function assertThatABackendUserWithFeUserEditRightsIsUnableToCreateOAuth2FrontendConfigurations(string $userName): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, $userName, 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "create fe_config ..." links
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-fe-1', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-fe-2', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-fe-3', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-create-fe-4', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertCount(4, $frontendUserOauth2ProviderConfigurations, 'assert: 0 oauth2 provider configuration exists for frontend users');

        self::assertEquals(0, (int)$frontendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: frontend user oauth2 provider configuration (1) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[0]['provider'], 'assert: frontend user oauth2 provider configuration (1) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: frontend user oauth2 provider configuration (1) is valid');

        self::assertEquals(0, (int)$frontendUserOauth2ProviderConfigurations[1]['parentid'], 'assert: frontend user oauth2 provider configuration (2) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[1]['provider'], 'assert: frontend user oauth2 provider configuration (2) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[1]['identifier'], 'assert: frontend user oauth2 provider configuration (2) is valid');

        self::assertEquals(0, (int)$frontendUserOauth2ProviderConfigurations[2]['parentid'], 'assert: frontend user oauth2 provider configuration (3) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[2]['provider'], 'assert: frontend user oauth2 provider configuration (3) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[2]['identifier'], 'assert: frontend user oauth2 provider configuration (3) is valid');

        self::assertEquals(0, (int)$frontendUserOauth2ProviderConfigurations[3]['parentid'], 'assert: frontend user oauth2 provider configuration (4) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[3]['provider'], 'assert: frontend user oauth2 provider configuration (4) is valid');
        self::assertEquals('_invalid_', $frontendUserOauth2ProviderConfigurations[3]['identifier'], 'assert: frontend user oauth2 provider configuration (4) is valid');
    }

    /**
     * @test
     */
    public function assertThatAnAdminBackendUserIsAbleToDeactivateOAuth2FrontendConfigurationsViaRecordCommitEndpoint(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createFrontendUserOauth2ProviderConfiguration(1, 1000, 'gitlab', 'remote-identity1');
        $this->createFrontendUserOauth2ProviderConfiguration(2, 1001, 'gitlab', 'remote-identity2');

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'admin', 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "deactivate fe_config ..." links
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-deactivate-fe-1', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-deactivate-fe-2', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertCount(0, $frontendUserOauth2ProviderConfigurations, 'assert: 0 oauth2 provider configuration exists for frontend users');
    }

    public function assertThatABackendUserWithoutFeUserEditRightsIsUnableToDeactivateOAuth2FrontendConfigurationsDataProvider(): \Generator
    {
        yield 'user2' => [
            'userName' => 'user2',
        ];

        yield 'user3' => [
            'userName' => 'user3',
        ];
    }

    /**
     * @test
     * @dataProvider assertThatABackendUserWithoutFeUserEditRightsIsUnableToDeactivateOAuth2FrontendConfigurationsDataProvider
     */
    public function assertThatABackendUserWithoutFeUserEditRightsIsUnableToDeactivateOAuth2FrontendConfigurations(string $userName): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createFrontendUserOauth2ProviderConfiguration(1, 1000, 'gitlab', 'remote-identity1');
        $this->createFrontendUserOauth2ProviderConfiguration(2, 1001, 'gitlab', 'remote-identity2');

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, $userName, 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "deactivate fe_config ..." links
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-deactivate-fe-1', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-deactivate-fe-2', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertCount(2, $frontendUserOauth2ProviderConfigurations, 'assert: 2 oauth2 provider configuration exists for frontend users');
    }

    public function assertThatABackendUserWithFeUserEditRightsIsAbleToDeactivateOAuth2FrontendConfigurationsDataProvider(): \Generator
    {
        yield 'user4' => [
            'userName' => 'user4',
        ];

        yield 'user5' => [
            'userName' => 'user5',
        ];
    }

    /**
     * @test
     * @dataProvider assertThatABackendUserWithFeUserEditRightsIsAbleToDeactivateOAuth2FrontendConfigurationsDataProvider
     */
    public function assertThatABackendUserWithFeUserEditRightsIsAbleToDeactivateOAuth2FrontendConfigurations(string $userName): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createFrontendUserOauth2ProviderConfiguration(1, 1000, 'gitlab', 'remote-identity1');
        $this->createFrontendUserOauth2ProviderConfiguration(2, 1001, 'gitlab', 'remote-identity2');

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, $userName, 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "deactivate fe_config ..." links
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-deactivate-fe-1', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-deactivate-fe-2', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertCount(0, $frontendUserOauth2ProviderConfigurations, 'assert: 2 oauth2 provider configuration exists for frontend users');
    }

    /**
     * @test
     */
    public function assertThatAnAdminBackendUserIsUnableToOverrideOAuth2FrontendConfigurationsViaRecordCommitEndpoint(): void
    {
        $this->resetSessionData();

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'admin', 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "override parentid=1 for fe_config=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createFrontendUserOauth2ProviderConfiguration(1, 1000, 'gitlab1-fe', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-fe-1', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertEquals(1000, (int)$frontendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: frontend user oauth2 provider configuration (1) is valid');
        self::assertEquals('gitlab1-fe', $frontendUserOauth2ProviderConfigurations[0]['provider'], 'assert: frontend user oauth2 provider configuration (1) is valid');
        self::assertEquals('remote-id', $frontendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: frontend user oauth2 provider configuration (1) is valid');

        // Click "override provider=gitlab, identifier=existing1 for fe_config=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createFrontendUserOauth2ProviderConfiguration(1, 1000, 'gitlab1-fe', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-fe-2', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertEquals(1000, (int)$frontendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: frontend user oauth2 provider configuration (2) is valid');
        self::assertEquals('gitlab1-fe', $frontendUserOauth2ProviderConfigurations[0]['provider'], 'assert: frontend user oauth2 provider configuration (2) is valid');
        self::assertEquals('remote-id', $frontendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: frontend user oauth2 provider configuration (2) is valid');

        // Click "override provider=gitlab, identifier=existing2, parentid=1 for fe_config=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createFrontendUserOauth2ProviderConfiguration(1, 1000, 'gitlab1-fe', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-fe-3', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertEquals(1000, (int)$frontendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: frontend user oauth2 provider configuration (3) is valid');
        self::assertEquals('gitlab1-fe', $frontendUserOauth2ProviderConfigurations[0]['provider'], 'assert: frontend user oauth2 provider configuration (3) is valid');
        self::assertEquals('remote-id', $frontendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: frontend user oauth2 provider configuration (3) is valid');

        // Click "override provider=gitlab, identifier=existing3, parentid=1 for fe_config=1 and fe_user=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createFrontendUserOauth2ProviderConfiguration(1, 1000, 'gitlab1-fe', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-fe-4', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertEquals(1000, (int)$frontendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: frontend user oauth2 provider configuration (4) is valid');
        self::assertEquals('gitlab1-fe', $frontendUserOauth2ProviderConfigurations[0]['provider'], 'assert: frontend user oauth2 provider configuration (4) is valid');
        self::assertEquals('remote-id', $frontendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: frontend user oauth2 provider configuration (4) is valid');
    }

    /**
     * @test
     */
    public function assertThatABackendUserWithFeUserEditRightsIsUnableToOverrideOAuth2FrontendConfigurationsViaRecordCommitEndpoint(): void
    {
        $this->resetSessionData();

        $responseData = $this->loginIntoBackendWithUsernameAndPassword(self::SITE1_BASE_URI, 'user4', 'password');
        $oauth2ProvidersTestBackendModuleResponseData = $this->goToOauth2ProvidersTestBackendModule($responseData);

        // Click "override parentid=1 for fe_config=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createFrontendUserOauth2ProviderConfiguration(1, 1000, 'gitlab1-fe', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-fe-1', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertEquals(1000, (int)$frontendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: frontend user oauth2 provider configuration (1) is valid');
        self::assertEquals('gitlab1-fe', $frontendUserOauth2ProviderConfigurations[0]['provider'], 'assert: frontend user oauth2 provider configuration (1) is valid');
        self::assertEquals('remote-id', $frontendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: frontend user oauth2 provider configuration (1) is valid');

        // Click "override provider=gitlab, identifier=existing1 for fe_config=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createFrontendUserOauth2ProviderConfiguration(1, 1000, 'gitlab1-fe', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-fe-2', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertEquals(1000, (int)$frontendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: frontend user oauth2 provider configuration (2) is valid');
        self::assertEquals('gitlab1-fe', $frontendUserOauth2ProviderConfigurations[0]['provider'], 'assert: frontend user oauth2 provider configuration (2) is valid');
        self::assertEquals('remote-id', $frontendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: frontend user oauth2 provider configuration (2) is valid');

        // Click "override provider=gitlab, identifier=existing2, parentid=1 for fe_config=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createFrontendUserOauth2ProviderConfiguration(1, 1000, 'gitlab1-fe', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-fe-3', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertEquals(1000, (int)$frontendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: frontend user oauth2 provider configuration (3) is valid');
        self::assertEquals('gitlab1-fe', $frontendUserOauth2ProviderConfigurations[0]['provider'], 'assert: frontend user oauth2 provider configuration (3) is valid');
        self::assertEquals('remote-id', $frontendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: frontend user oauth2 provider configuration (3) is valid');

        // Click "override provider=gitlab, identifier=existing3, parentid=1 for fe_config=1 and fe_user=1" link
        $this->resetOauth2ProviderConfigurations();
        $this->createFrontendUserOauth2ProviderConfiguration(1, 1000, 'gitlab1-fe', 'remote-id');
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-override-fe-4', $oauth2ProvidersTestBackendModuleResponseData);
        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertEquals(1000, (int)$frontendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: frontend user oauth2 provider configuration (4) is valid');
        self::assertEquals('gitlab1-fe', $frontendUserOauth2ProviderConfigurations[0]['provider'], 'assert: frontend user oauth2 provider configuration (4) is valid');
        self::assertEquals('remote-id', $frontendUserOauth2ProviderConfigurations[0]['identifier'], 'assert: frontend user oauth2 provider configuration (4) is valid');
    }
}
