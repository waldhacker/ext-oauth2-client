<?php

declare(strict_types=1);

/*
 * This file is part of the OAuth2 Client extension for TYPO3
 * - (c) 2021 waldhacker UG (haftungsbeschränkt)
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

namespace Waldhacker\Oauth2Client\Database\Query\Restriction;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\EnforceableQueryRestrictionInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Allow access only to tx_oauth2_feuser_provider_configuration records that were created
 * for the current logged in frontend user.
 */
class Oauth2FeUserProviderConfigurationRestriction implements QueryRestrictionInterface, EnforceableQueryRestrictionInterface
{
    private int $frontendUserId;
    private bool $isBackendUser;

    public function __construct(Context $context = null)
    {
        $context = $context ?? GeneralUtility::makeInstance(Context::class);

        $this->frontendUserId = 0;
        $this->isBackendUser = false;
        if ($context->hasAspect('frontend.user')) {
            $this->frontendUserId = (int)$context->getPropertyFromAspect('frontend.user', 'id');
        }

        if (
            ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && is_int($GLOBALS['TYPO3_REQUEST']->getAttribute('applicationType'))
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
        ) {
            $this->isBackendUser = true;
        }
    }

    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        $userWithEditRightsColumn = $GLOBALS['TCA']['tx_oauth2_feuser_provider_configuration']['ctrl']['enablecolumns']['fe_user'] ?? 'parentid';

        foreach ($queriedTables as $tableAlias => $tableName) {
            if ($tableName !== 'tx_oauth2_feuser_provider_configuration' || $this->isBackendUser) {
                continue;
            }

            $constraints[] = $expressionBuilder->eq(
                $tableAlias . '.' . $userWithEditRightsColumn,
                $this->frontendUserId
            );
        }

        return $expressionBuilder->and(...$constraints);
    }

    public function isEnforced(): bool
    {
        return true;
    }
}
