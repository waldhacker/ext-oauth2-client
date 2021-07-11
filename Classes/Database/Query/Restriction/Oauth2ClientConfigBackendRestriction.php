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

namespace Waldhacker\Oauth2Client\Database\Query\Restriction;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\EnforceableQueryRestrictionInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Allow access only to tx_oauth2_client_configs records that were created
 * for the current logged in backend user.
 */
class Oauth2ClientConfigBackendRestriction implements QueryRestrictionInterface, EnforceableQueryRestrictionInterface
{
    protected Context $context;
    protected int $backendUserId;

    public function __construct(Context $context = null)
    {
        $this->context = $context ?? $this->getContext();

        $this->backendUserId = 0;
        if ($this->context->hasAspect('backend.user')) {
            $this->backendUserId = (int)$this->context->getPropertyFromAspect('backend.user', 'id');
        }
    }

    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        $userWithEditRightsColumn = $GLOBALS['TCA']['tx_oauth2_client_configs']['ctrl']['enablecolumns']['be_user'] ?? 'parentid';

        foreach ($queriedTables as $tableAlias => $tableName) {
            if ($tableName !== 'tx_oauth2_client_configs') {
                continue;
            }

            $constraints[] = $expressionBuilder->eq(
                $tableAlias . '.' . $userWithEditRightsColumn,
                $this->backendUserId
            );
        }

        return $expressionBuilder->andX(...$constraints);
    }

    public function isEnforced(): bool
    {
        return true;
    }

    protected function getContext(): Context
    {
        /** @var Context $context */
        $context = GeneralUtility::makeInstance(Context::class);
        return $context;
    }
}
