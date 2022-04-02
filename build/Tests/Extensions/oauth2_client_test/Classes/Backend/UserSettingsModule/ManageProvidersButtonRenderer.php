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

namespace Waldhacker\Oauth2ClientTest\Backend\UserSettingsModule;

use TYPO3\CMS\Backend\Routing\UriBuilder;

class ManageProvidersButtonRenderer
{
    private UriBuilder $uriBuilder;

    public function __construct(UriBuilder $uriBuilder)
    {
        $this->uriBuilder = $uriBuilder;
    }

    public function render(): string
    {
        $html = '<a id="oauth2test-manage-providers" href="' . htmlspecialchars((string)$this->uriBuilder->buildUriFromRoute('oauth2_manage_providers_test')) . '">';
        $html .= 'manage OAuth2 providers test';
        $html .= '</a>';
        return $html;
    }
}
