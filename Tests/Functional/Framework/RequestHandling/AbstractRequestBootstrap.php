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

use Composer\Autoload\ClassLoader;
use TYPO3\CMS\Backend\Http\Application as BackendApplication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Frontend\Http\Application as FrontendApplication;
use TYPO3\JsonResponse\GlobalStates;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

abstract class AbstractRequestBootstrap
{
    protected string $documentRoot;
    protected array $requestArguments;
    private ClassLoader $classLoader;
    protected InternalRequestContext $context;
    protected ExtendedInternalRequest $request;
    private array $result = ['status' => 'failure', 'content' => null, 'error' => null];

    public function __construct(string $documentRoot, string $vendorPath, array $requestArguments = null)
    {
        $this->documentRoot = $documentRoot;
        $this->requestArguments = $requestArguments;
        $this->initialize($vendorPath);
        $this->setGlobalVariables();
        register_shutdown_function([$this, 'output']);
    }

    private function initialize(string $vendorPath): void
    {
        $this->classLoader = require_once $vendorPath . '/autoload.php';
    }

    abstract protected function setGlobalVariables(): void;

    public function executeAndOutput(): void
    {
        global $TSFE, $BE_USER;

        ob_start();
        try {
            $override = $this->context->getGlobalSettings() ?? [];
            foreach ($override as $k => $v) {
                if (isset($GLOBALS[$k])) {
                    ArrayUtility::mergeRecursiveWithOverrule($GLOBALS[$k], $override[$k]);
                } else {
                    $GLOBALS[$k] = $override[$k];
                }
            }

            chdir($_SERVER['DOCUMENT_ROOT']);
            SystemEnvironmentBuilder::run(static::ENTRY_LEVEL, static::REQUEST_TYPE);
            $container = Bootstrap::init($this->classLoader);

            $override = $this->context->getGlobalSettings() ?? [];
            foreach ($GLOBALS as $k => $v) {
                if (isset($override[$k])) {
                    ArrayUtility::mergeRecursiveWithOverrule($GLOBALS[$k], $override[$k]);
                }
            }

            $applicationClass = static::REQUEST_TYPE === SystemEnvironmentBuilder::REQUESTTYPE_FE
                                ? FrontendApplication::class
                                : BackendApplication::class;

            $container->get($applicationClass)->run();
            $this->result['status'] = 'success';
            $this->result['content'] = static::getContent();
        } catch (\Throwable $exception) {
            $this->result['error'] = $exception->__toString();
            $this->result['exception'] = [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ];
        }

        ob_end_clean();
    }

    public function output(): void
    {
        if (empty($this->result['content']) && empty($this->result['error'])) {
            $this->result['status'] = 'success';
            $this->result['content'] = [
                'statusCode' => 200,
                'reasonPhrase' => '',
                'headers' => GlobalStates::getHeaders(),
                'body' => null,
            ];
        }

        echo json_encode($this->result);
    }

    /**
     * @return string|array|null
     */
    private static function getContent()
    {
        $content = ob_get_contents();
        $content = json_decode($content, true);
        return $content;
    }
}
