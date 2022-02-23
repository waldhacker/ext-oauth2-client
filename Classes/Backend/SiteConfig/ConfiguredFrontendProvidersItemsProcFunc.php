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

namespace Waldhacker\Oauth2Client\Backend\SiteConfig;

use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;

class ConfiguredFrontendProvidersItemsProcFunc
{
    private Oauth2ProviderManager $oauth2ProviderManager;

    public function __construct(Oauth2ProviderManager $oauth2ProviderManager)
    {
        $this->oauth2ProviderManager = $oauth2ProviderManager;
    }

    public function getItems(array &$params): void
    {
        foreach ($this->oauth2ProviderManager->getConfiguredFrontendProviders() ?? [] as $provider) {
            $params['items'][] = [$provider->getLabel(), $provider->getIdentifier()];
        }
    }
}
