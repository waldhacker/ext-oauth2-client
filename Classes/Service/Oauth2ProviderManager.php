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

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use Waldhacker\Oauth2Client\Domain\Model\ProviderConfiguration;

class Oauth2ProviderManager
{
    private array $providerConfigurations = [];

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $extensionConfiguration = $extensionConfiguration->get('oauth2_client');
        if (isset($extensionConfiguration['providers'])) {
            foreach ($extensionConfiguration['providers'] as $identifier => $provider) {
                $this->providerConfigurations[$identifier] = new ProviderConfiguration(
                    $identifier,
                    $provider['label'],
                    $provider['description'] ?? '',
                    $provider['iconIdentifier'] ?? 'actions-key',
                    $provider['implementationClassName'] ?? GenericProvider::class,
                    $provider['options'] ?? []
                );
            }
        }
    }

    /**
     * @api
     * @param string $providerId
     * @param string|null $redirectUrl
     * @return AbstractProvider
     */
    public function createProvider(string $providerId, ?string $redirectUrl = null): AbstractProvider
    {
        if (!isset($this->providerConfigurations[$providerId])) {
            throw new \InvalidArgumentException('No such provider: ' . $providerId);
        }
        $providerConfiguration = $this->providerConfigurations[$providerId];
        $configuration = $providerConfiguration->getOptions();
        if ($redirectUrl !== null) {
            $configuration['redirectUri'] = $redirectUrl;
        }
        $implementationClassName = $providerConfiguration->getImplementationClassName();
        return new $implementationClassName($configuration);
    }

    public function getConfiguredProviders(): ?array
    {
        $providers = $this->providerConfigurations;
        if (count($providers) > 0) {
            return $providers;
        }
        return null;
    }
}
