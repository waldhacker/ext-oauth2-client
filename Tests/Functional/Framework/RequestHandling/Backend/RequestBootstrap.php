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

namespace Waldhacker\Oauth2Client\Tests\Functional\Framework\RequestHandling\Backend;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use Waldhacker\Oauth2Client\Tests\Functional\Framework\RequestHandling\AbstractRequestBootstrap;
use Waldhacker\Oauth2Client\Tests\Functional\Framework\RequestHandling\ExtendedInternalRequest;

class RequestBootstrap extends AbstractRequestBootstrap
{
    protected const ENTRY_LEVEL = 1;
    protected const REQUEST_TYPE = SystemEnvironmentBuilder::REQUESTTYPE_BE;

    protected function setGlobalVariables(): void
    {
        if (empty($this->requestArguments)) {
            die('No JSON encoded arguments given');
        }

        if (empty($this->documentRoot)) {
            die('No documentRoot given');
        }

        if (!empty($this->requestArguments['requestUrl'])) {
            die('Using request URL has been removed, use request object instead');
        }

        if (empty($this->requestArguments['request'])) {
            die('No request object given');
        }

        $this->context = InternalRequestContext::fromArray(json_decode($this->requestArguments['context'], true));
        $this->request = ExtendedInternalRequest::fromArray(json_decode($this->requestArguments['request'], true));

        $requestUrlParts = parse_url((string)$this->request->getUri());

        // Populating $_GET and $_REQUEST is query part is set:
        if (isset($requestUrlParts['query'])) {
            parse_str($requestUrlParts['query'], $_GET);
            parse_str($requestUrlParts['query'], $_REQUEST);
        }

        $_POST = method_exists($this->request, 'getParsedBody') ? $this->request->getParsedBody() : [];
        $_COOKIE = method_exists($this->request, 'getCookieParams') ? $this->request->getCookieParams() : [];

        // Setting up the server environment
        $_SERVER = [];
        $_SERVER['X_TYPO3_TESTING_FRAMEWORK'] = [
            'context' => $this->context,
            'request' => $this->request,
        ];
        $_SERVER['DOCUMENT_ROOT'] = $this->documentRoot;
        $_SERVER['HTTP_USER_AGENT'] = 'TYPO3 Functional Test Request';
        $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = isset($requestUrlParts['host']) ? $requestUrlParts['host'] : 'localhost';
        $_SERVER['SERVER_ADDR'] = $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $_SERVER['DOCUMENT_URI'] = '/typo3/index.php';
        $_SERVER['SCRIPT_FILENAME'] = $_SERVER['_'] = $_SERVER['PATH_TRANSLATED'] = $this->documentRoot . '/typo3/index.php';
        $_SERVER['QUERY_STRING'] = (isset($requestUrlParts['query']) ? $requestUrlParts['query'] : '');
        $_SERVER['REQUEST_URI'] = $requestUrlParts['path'] . (isset($requestUrlParts['query']) ? '?' . $requestUrlParts['query'] : '');
        $_SERVER['REQUEST_METHOD'] = $this->request->getMethod();

        // Define HTTPS and server port:
        if (isset($requestUrlParts['scheme'])) {
            if ($requestUrlParts['scheme'] === 'https') {
                $_SERVER['HTTPS'] = 'on';
                $_SERVER['SERVER_PORT'] = '443';
            } else {
                $_SERVER['SERVER_PORT'] = '80';
            }
        }

        // Define a port if used in the URL:
        if (isset($requestUrlParts['port'])) {
            $_SERVER['SERVER_PORT'] = $requestUrlParts['port'];
        }

        if (!is_dir($_SERVER['DOCUMENT_ROOT'])) {
            die('Document root directory "' . $_SERVER['DOCUMENT_ROOT'] . '" does not exist');
        }

        if (!is_file($_SERVER['SCRIPT_FILENAME'])) {
            die('Script file "' . $_SERVER['SCRIPT_FILENAME'] . '" does not exist');
        }

        putenv('TYPO3_CONTEXT=Testing/Backend');
    }
}
