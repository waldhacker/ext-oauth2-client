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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\EnforceableQueryRestrictionInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Allow access only to tx_oauth2_beuser_provider_configuration records that were created
 * for the current logged in backend user.
 */
class Oauth2BeUserProviderConfigurationRestriction implements QueryRestrictionInterface, EnforceableQueryRestrictionInterface
{
    private const OAUTH2_BE_TABLE = 'tx_oauth2_beuser_provider_configuration';
    private int $backendUserId;
    private bool $isAdmin;

    public function __construct(Context $context = null)
    {
        $context = $context ?? GeneralUtility::makeInstance(Context::class);

        $this->backendUserId = 0;
        $this->isAdmin = false;
        if ($context->hasAspect('backend.user')) {
            $this->backendUserId = (int)$context->getPropertyFromAspect('backend.user', 'id');
            $this->isAdmin = $context->getPropertyFromAspect('backend.user', 'isAdmin');
        }
    }

    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        $userWithEditRightsColumn = $GLOBALS['TCA'][self::OAUTH2_BE_TABLE]['ctrl']['enablecolumns']['be_user'] ?? 'parentid';

        foreach ($queriedTables as $tableAlias => $tableName) {
            if ($tableName !== self::OAUTH2_BE_TABLE || $this->isAdmin) {
                continue;
            }

            $constraints[] = $expressionBuilder->eq(
                $tableAlias . '.' . $userWithEditRightsColumn,
                $this->backendUserId
            );
        }

        return $expressionBuilder->and(...$constraints);
    }

    public function isEnforced(): bool
    {
        return true;
    }
}
