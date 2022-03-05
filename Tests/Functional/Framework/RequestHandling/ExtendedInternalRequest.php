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

namespace Waldhacker\Oauth2Client\Tests\Functional\Framework\RequestHandling;

use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class ExtendedInternalRequest extends InternalRequest
{
    protected $parsedBody = [];
    protected $cookieParams = [];

    public function withHeaders(array $headers)
    {
        $request = $this;
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        return $request;
    }

    public function getParsedBody(): ?array
    {
        return $this->parsedBody;
    }

    public function withParsedBody(?array $parsedBody = null): InternalRequest
    {
        $clonedObject = clone $this;
        $clonedObject->parsedBody = $parsedBody;
        return $clonedObject;
    }

    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies)
    {
        $clonedObject = clone $this;
        $clonedObject->cookieParams = $cookies;
        return $clonedObject;
    }

    public function jsonSerialize(): array
    {
        return array_replace_recursive(
            parent::jsonSerialize(),
            [
                'parsedBody' => $this->parsedBody,
                'cookieParams' => $this->cookieParams,
            ]
        );
    }
}
