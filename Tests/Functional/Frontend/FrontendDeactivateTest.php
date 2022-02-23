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

use Waldhacker\Oauth2Client\Tests\Functional\Framework\FunctionalTestCase;

class FrontendDeactivateTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function assertThatAFrontendUserIsAbleToOnlyDeactivateAOAuth2ProvidersWhichHeOwns(): void
    {
        $this->resetSessionData();
        $this->resetOauth2ProviderConfigurations();
        $this->createFrontendUserOauth2ProviderConfiguration(1, 1000, 'gitlab3-both', 'user1-gitlab3-both-remote-identity');
        $this->createFrontendUserOauth2ProviderConfiguration(2, 1001, 'gitlab3-both', 'user2-gitlab3-both-remote-identity');

        $loggedInFrontendUserUid = 1000;
        $siteBaseUri = self::SITE1_BASE_URI;
        $languageSlug = '/en';

        // Login into frontend
        $responseData = $this->loginIntoFrontendWithUsernameAndPassword($siteBaseUri, $languageSlug, 'user1', 'password');

        // Click "deactivate config=1" link
        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-deactivate-fe-1', $responseData);
        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest($siteBaseUri . $oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertCount(1, $frontendUserOauth2ProviderConfigurations, 'assert: 1 oauth2 provider configuration exists for frontend users');
        self::assertEquals(1001, (int)$frontendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: frontend user oauth2 provider configuration is valid');

        // Click "deactivate config=2" link
        $responseData = $this->goToOauth2ProvidersTestFrontendPage($siteBaseUri, $languageSlug, $responseData);
        $oauth2DeactivationUri = $this->extractLinkHrefFromResponseData('oauth2test-deactivate-fe-2', $responseData);
        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest($siteBaseUri . $oauth2DeactivationUri, $responseData['cookieData']));

        $frontendUserOauth2ProviderConfigurations = $this->getFrontendUserOauth2ProviderConfigurations();
        self::assertCount(1, $frontendUserOauth2ProviderConfigurations, 'assert: 1 oauth2 provider configuration exists for frontend users');
        self::assertEquals(1001, (int)$frontendUserOauth2ProviderConfigurations[0]['parentid'], 'assert: frontend user oauth2 provider configuration is valid');
    }
}
