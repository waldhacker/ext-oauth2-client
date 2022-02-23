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
    private array $scopes;
    private array $options;
    private array $collaborators;

    public function __construct(
        string $identifier,
        string $label,
        string $description,
        string $iconIdentifier,
        string $implementationClassName,
        array $scopes,
        array $options,
        array $collaborators
    ) {
        $this->label = $label;
        $this->description = $description;
        $this->iconIdentifier = $iconIdentifier;
        $this->identifier = $identifier;
        if (!class_exists($implementationClassName) || !is_a($implementationClassName, AbstractProvider::class, true)) {
            throw new \InvalidArgumentException('Registered class ' . $implementationClassName . ' does not exist or is not an implementation of ' . AbstractProvider::class, 1642867945);
        }
        $this->implementationClassName = $implementationClassName;
        $this->scopes = $scopes;
        $this->options = $options;
        $this->collaborators = $collaborators;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIconIdentifier(): string
    {
        return $this->iconIdentifier;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getImplementationClassName(): string
    {
        return $this->implementationClassName;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getCollaborators(): array
    {
        return $this->collaborators;
    }
}
