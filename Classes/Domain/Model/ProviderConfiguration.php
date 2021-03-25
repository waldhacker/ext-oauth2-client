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

namespace Waldhacker\Oauth2Client\Domain\Model;

use League\OAuth2\Client\Provider\AbstractProvider;

class ProviderConfiguration
{
    private string $label;
    private string $description;
    private string $iconIdentifier;
    private string $identifier;
    private string $implementationClassName;
    private array $options;

    public function __construct(
        string $identifier,
        string $label,
        string $description,
        string $iconIdentifier,
        string $implementationClassName,
        array $options
    ) {
        $this->label = $label;
        $this->description = $description;
        $this->iconIdentifier = $iconIdentifier;
        $this->identifier = $identifier;
        if (!class_exists($implementationClassName) || !is_a($implementationClassName, AbstractProvider::class, true)) {
            throw new \InvalidArgumentException('Registered class ' . $implementationClassName . ' does not exist or is not an implementation of ' . AbstractProvider::class);
        }
        $this->implementationClassName = $implementationClassName;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getIconIdentifier(): string
    {
        return $this->iconIdentifier;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getImplementationClassName(): string
    {
        return $this->implementationClassName;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
