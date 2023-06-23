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

namespace Waldhacker\Oauth2Client\Tests\Functional\Framework;

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use Waldhacker\Oauth2Client\Backend\DataHandling\DataHandlerHook;
use Waldhacker\Oauth2Client\Database\Query\Restriction\Oauth2BeUserProviderConfigurationRestriction;
use Waldhacker\Oauth2Client\Database\Query\Restriction\Oauth2FeUserProviderConfigurationRestriction;
use Waldhacker\Oauth2Client\Tests\Functional\Framework\FormHandling\DataExtractor;
use Waldhacker\Oauth2Client\Tests\Functional\Framework\FormHandling\DataPusher;
use Waldhacker\Oauth2Client\Tests\Functional\Framework\RequestHandling\Typo3RequestAwareTestTrait;
use Waldhacker\Oauth2Client\Tests\Functional\Framework\SiteHandling\SiteBasedTestTrait;

abstract class FunctionalTestCase extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    use SiteBasedTestTrait;
    use Typo3RequestAwareTestTrait;

    public const DEFAULT_TYPO3_CONF_VARS = [
        'BE' => [
            'lockSSL' => false,
        ],
        'SYS' => [
            'encryptionKey' => '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6',
            'features' => [
                'security.backend.enforceReferrer' => false,
            ],
        ],
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_GB.UTF8', 'iso' => 'en', 'hrefLang' => 'en-GB', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected const SITE1_HOST = 'site1';
    protected const SITE1_BASE_URI = 'http://' . self::SITE1_HOST;
    protected const SITE2_HOST = 'site2';
    protected const SITE2_BASE_URI = 'http://' . self::SITE2_HOST;

    protected $pathsToLinkInTestInstance = [
        'typo3conf/ext/oauth2_client/Tests/Functional/Fixtures/Frontend/AdditionalConfiguration.php' => 'typo3conf/AdditionalConfiguration.php',
    ];

    protected $coreExtensionsToLoad = [
        'core',
        'backend',
        'frontend',
        'extbase',
        'install',
        'recordlist',
        'felogin',
        'fluid',
        'fluid_styled_content',
        'setup',
    ];

    protected $testExtensionsToLoad = [
        'typo3conf/ext/oauth2_client',
        'typo3conf/ext/oauth2_client_test',
        'typo3conf/ext/json_response',
    ];

    protected $frameworkExtensionsToLoad = [];

    protected $rootPageUid = 1;

    protected $databaseScenarioFile = __DIR__ . '/../Fixtures/Frontend/StandardPagesScenario.yaml';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::initializeDatabaseSnapshot();
    }

    public static function tearDownAfterClass(): void
    {
        static::destroyDatabaseSnapshot();
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->writeSiteConfiguration(
            'site1',
            $this->buildSiteConfiguration(2000, self::SITE1_BASE_URI . '/'),
            [
                array_replace_recursive(
                    $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                    [
                        'enabled_oauth2_providers' => 'gitlab1-fe, gitlab3-both',
                        'oauth2_callback_slug' => '',
                        'oauth2_storage_pid' => 1000,
                    ]
                ),
                array_replace_recursive(
                    $this->buildLanguageConfiguration('DE', '/de/'),
                    [
                        'enabled_oauth2_providers' => 'gitlab1-fe, gitlab4-fe, gitlab8-fe',
                        'oauth2_callback_slug' => '',
                        'oauth2_storage_pid' => 1000,
                    ]
                )
            ],
            [
                $this->buildErrorHandlingConfiguration('Fluid', [404])
            ]
        );

        $this->writeSiteConfiguration(
            'site2',
            $this->buildSiteConfiguration(3000, self::SITE2_BASE_URI . '/'),
            [
                array_replace_recursive(
                    $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                    [
                        'enabled_oauth2_providers' => 'gitlab4-fe, gitlab6-both',
                        'oauth2_callback_slug' => '',
                        'oauth2_storage_pid' => 1000,
                    ]
                ),
                array_replace_recursive(
                    $this->buildLanguageConfiguration('DE', '/de/'),
                    [
                        'enabled_oauth2_providers' => 'gitlab1-fe, gitlab7-fe',
                        'oauth2_callback_slug' => '',
                        'oauth2_storage_pid' => 1000,
                    ]
                )
            ],
            [
                $this->buildErrorHandlingConfiguration('Fluid', [404])
            ]
        );

        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']);
        parent::tearDown();
    }

    protected function setUpDatabase(): void
    {
        $backendUser = $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        unset(
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][1625556930],
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][1625556930],
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][1625556930],
            $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][Oauth2BeUserProviderConfigurationRestriction::class],
            $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][Oauth2FeUserProviderConfigurationRestriction::class]
        );

        $factory = DataHandlerFactory::fromYamlFile($this->databaseScenarioFile);
        $writer = DataHandlerWriter::withBackendUser($backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][1625556930] = DataHandlerHook::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][1625556930] = DataHandlerHook::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][1625556930] = DataHandlerHook::class;
        $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][Oauth2BeUserProviderConfigurationRestriction::class] = [];
        $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][Oauth2FeUserProviderConfigurationRestriction::class] = [];
    }

    protected function getBackendUserOauth2ProviderConfigurations(): array
    {
        $qb = $this->getConnectionPool()->getQueryBuilderForTable('tx_oauth2_beuser_provider_configuration');
        $qb->getRestrictions()->removeByType(Oauth2BeUserProviderConfigurationRestriction::class);
        return $qb->select('*')->from('tx_oauth2_beuser_provider_configuration')->execute()->fetchAllAssociative();
    }

    protected function getFrontendUserOauth2ProviderConfigurations(): array
    {
        $qb = $this->getConnectionPool()->getQueryBuilderForTable('tx_oauth2_feuser_provider_configuration');
        $qb->getRestrictions()->removeByType(Oauth2FeUserProviderConfigurationRestriction::class);
        return $qb->select('*')->from('tx_oauth2_feuser_provider_configuration')->execute()->fetchAllAssociative();
    }

    protected function getBackendSessionData(): array
    {
        $qb = $this->getConnectionPool()->getQueryBuilderForTable('be_sessions');
        $rows = $qb->select('*')->from('be_sessions')->execute()->fetchAllAssociative();
        return $this->unserializeSessionDataFromSessions($rows);
    }

    protected function getFrontendSessionData(): array
    {
        $qb = $this->getConnectionPool()->getQueryBuilderForTable('fe_sessions');
        $rows = $qb->select('*')->from('fe_sessions')->execute()->fetchAllAssociative();
        return $this->unserializeSessionDataFromSessions($rows);
    }

    protected function getBackendSessionDataByUser(int $userId): array
    {
        return array_values(array_filter($this->getBackendSessionData(), fn (array $session): bool => (int)$session['ses_userid'] === $userId));
    }

    protected function getFrontendSessionDataByUser(int $userId): array
    {
        return array_values(array_filter($this->getFrontendSessionData(), fn (array $session): bool => (int)$session['ses_userid'] === $userId));
    }

    protected function getOauth2BackendSessionData(): array
    {
        return array_values(array_filter($this->getBackendSessionData(), fn (array $session): bool => strpos($session['ses_data_original'], 'oauth2') !== false));
    }

    protected function getOauth2FrontendSessionData(): array
    {
        return array_values(array_filter($this->getFrontendSessionData(), fn (array $session): bool => strpos($session['ses_data_original'], 'oauth2') !== false));
    }

    protected function removeOauth2BackendSessionData(): void
    {
        $qb = $this->getConnectionPool()->getQueryBuilderForTable('be_sessions');
        $qb->delete('be_sessions')->where($qb->expr()->in('ses_id', $qb->createNamedParameter(array_column($this->getOauth2BackendSessionData(), 'ses_id'), Connection::PARAM_STR_ARRAY)))->execute();
    }

    protected function removeOauth2FrontendSessionData(): void
    {
        $qb = $this->getConnectionPool()->getQueryBuilderForTable('fe_sessions');
        $qb->delete('fe_sessions')->where($qb->expr()->in('ses_id', $qb->createNamedParameter(array_column($this->getOauth2FrontendSessionData(), 'ses_id'), Connection::PARAM_STR_ARRAY)))->execute();
    }

    protected function deleteFrontendUser(int $userId): void
    {
        $qb = $this->getConnectionPool()->getQueryBuilderForTable('fe_users');
        $qb->update('fe_users')->set('deleted', 1)->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId, \PDO::PARAM_INT)))->execute();
    }

    protected function deleteBackendUser(int $userId): void
    {
        $qb = $this->getConnectionPool()->getQueryBuilderForTable('be_users');
        $qb->update('be_users')->set('deleted', 1)->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId, \PDO::PARAM_INT)))->execute();
    }

    protected function createBackendUserOauth2ProviderConfiguration(int $uid, int $userId, string $providerId, string $remoteIdentifier): void
    {
        $now = new \DateTime();
        $qb = $this->getConnectionPool()->getQueryBuilderForTable('tx_oauth2_beuser_provider_configuration');
        $qb->insert('tx_oauth2_beuser_provider_configuration')
            ->setValue('uid', $uid)
            ->setValue('pid', 0)
            ->setValue('crdate', $now->format('U'))
            ->setValue('tstamp', $now->format('U'))
            ->setValue('cruser_id', $userId)
            ->setValue('parentid', $userId)
            ->setValue('provider', $providerId)
            ->setValue('identifier', $remoteIdentifier)
            ->execute();
    }

    protected function createFrontendUserOauth2ProviderConfiguration(int $uid, int $userId, string $providerId, string $remoteIdentifier): void
    {
        $now = new \DateTime();
        $qb = $this->getConnectionPool()->getQueryBuilderForTable('tx_oauth2_feuser_provider_configuration');
        $qb->insert('tx_oauth2_feuser_provider_configuration')
            ->setValue('uid', $uid)
            ->setValue('pid', 0)
            ->setValue('crdate', $now->format('U'))
            ->setValue('tstamp', $now->format('U'))
            ->setValue('cruser_id', $userId)
            ->setValue('parentid', $userId)
            ->setValue('provider', $providerId)
            ->setValue('identifier', $remoteIdentifier)
            ->execute();
    }

    protected function resetSessionData(): void
    {
        $this->getConnectionPool()->getConnectionForTable('be_sessions')->truncate('be_sessions');
        $this->getConnectionPool()->getConnectionForTable('fe_sessions')->truncate('fe_sessions');
    }

    protected function resetOauth2ProviderConfigurations(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_oauth2_beuser_provider_configuration')->truncate('tx_oauth2_beuser_provider_configuration');
        $this->getConnectionPool()->getConnectionForTable('tx_oauth2_feuser_provider_configuration')->truncate('tx_oauth2_feuser_provider_configuration');
    }

    protected function loginIntoFrontendWithUsernameAndPassword(string $siteBaseUri, string $languageSlug, string $username, string $password): array
    {
        $uri = $siteBaseUri . $languageSlug . '/login';
        $responseData = $this->fetchFrontendPageContens($this->buildGetRequest($uri));
        $loginFormData = (new DataPusher(new DataExtractor($responseData['pageMarkup'])))
            ->with('user', $username)
            ->with('pass', $password)
            ->without('oauth2-provider');
        return $this->fetchFrontendPageContens($loginFormData->toPostRequest($this->buildGetRequest()));
    }

    protected function goToOauth2ProvidersTestFrontendPage(string $siteBaseUri, string $languageSlug, array $responseData): array
    {
        $uri = $siteBaseUri . $languageSlug . '/manage-providers-test';
        return $this->fetchFrontendPageContens($this->buildGetRequest($uri, $responseData['cookieData']));
    }

    protected function loginIntoBackendWithUsernameAndPassword(string $siteBaseUri, string $username, string $password): array
    {
        $uri = $siteBaseUri . '/typo3/login?loginProvider=1433416747';

        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($uri));
        $loginFormData = (new DataPusher(new DataExtractor($responseData['pageMarkup'])))
            ->with('username', $username)
            ->with('userident', $password);

        $request = $loginFormData->toPostRequest($this->buildGetRequest());

        return $this->fetchBackendPageContens($request);
    }

    protected function goToOauth2ProvidersTestBackendModule(array $responseData): array
    {
        // Goto user setup module
        $userSetupModuleUri = $this->extractLinkHrefFromResponseData('user_setup', $responseData);

        $responseData = $this->fetchBackendPageContens($this->buildGetRequest($userSetupModuleUri, $responseData['cookieData']));
        $cookies = $responseData['cookieData'];

        // Goto manage oauth2 providers test module
        $uri = $this->extractLinkHrefFromResponseData('oauth2test-manage-providers', $responseData);
        return $this->fetchBackendPageContens($this->buildGetRequest($uri, $responseData['cookieData']));
    }

    protected function extractLinkHrefFromResponseData(string $elementId, array $responseData): string
    {
        return $this->extractAttributeValueFromResponseData($elementId, 'href', $responseData);
    }

    protected function extractAttributeValueFromResponseData(string $elementId, string $attributeName, array $responseData): string
    {
        $document = new \DOMDocument();
        $document->loadHTML($responseData['pageMarkup']);
        $element = $document->getElementById($elementId);
        return $element->getAttribute($attributeName);
    }

    private function unserializeSessionDataFromSessions(array $sessions): array
    {
        return array_map(
            function (array $session): array {
                $session['ses_data_original'] = $session['ses_data'] ?? '';
                $session['ses_data'] = unserialize($session['ses_data'] ?? '', ['allowed_classes' => false]);
                return $session;
            },
            $sessions
        );
    }
}
