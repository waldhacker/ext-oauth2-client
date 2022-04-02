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

namespace Waldhacker\Oauth2Client\Tests\Functional\Fixtures;

use GuzzleHttp\Psr7\Response;

class InvalidAccessTokenResponseFromGitLabHttpMock
{
    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Mock for Waldhacker\Oauth2Client\Service\Oauth2Service::getUser() calls
     */
    public function getResponseQueue(): array
    {
        return [
            // Response from $provider->getAccessToken('authorization_code', ['code' => 'xxx']) (https://gitlab.site/oauth/token)
            new Response(401, [], ''),
        ];
    }
}
