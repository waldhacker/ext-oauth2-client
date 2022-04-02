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

namespace Waldhacker\Oauth2Client\Backend\Form\RenderType;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;

class Oauth2ProvidersElement extends AbstractFormElement
{
    private const BE_USERS_TABLE = 'be_users';
    private const FE_USERS_TABLE = 'fe_users';
    private Oauth2ProviderManager $oauth2ProviderManager;
    private UriBuilder $uriBuilder;

    public function __construct(NodeFactory $NodeFactory, array $data)
    {
        parent::__construct($NodeFactory, $data);
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->oauth2ProviderManager = GeneralUtility::makeInstance(Oauth2ProviderManager::class);
    }

    public function render()
    {
        $resultArray = $this->initializeResultArray();
        $tableName = $this->data['tableName'];

        if (!($tableName === self::BE_USERS_TABLE || $tableName === self::FE_USERS_TABLE)) {
            return $resultArray;
        }

        $html = $childHtml = [];
        $lang = $this->getLanguageService();
        $enabledLabel = htmlspecialchars($lang->sL('LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:labels.oauth2.enabled'), ENT_QUOTES | ENT_HTML5);
        $disabledLabel = htmlspecialchars($lang->sL('LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:labels.oauth2.disabled'), ENT_QUOTES | ENT_HTML5);
        $status = '<span class="label label-danger label-space-right t3js-oauth2-status-label" data-alternative-label="' . $enabledLabel . '">' . $disabledLabel . '</span>';

        $configuredProviders = $tableName === self::BE_USERS_TABLE
                               ? $this->oauth2ProviderManager->getConfiguredBackendProviders()
                               : $this->oauth2ProviderManager->getConfiguredFrontendProviders();
        $oauth2ProviderConfigurationTable = $tableName === self::BE_USERS_TABLE
                               ? 'tx_oauth2_beuser_provider_configuration'
                               : 'tx_oauth2_feuser_provider_configuration';

        if ($configuredProviders !== null) {
            $activeProvidersDb = array_column($this->data['parameterArray']['fieldConf']['children'], 'databaseRow');
            $activeProviders = array_combine(array_column($activeProvidersDb, 'provider'), $activeProvidersDb);

            // Check if remaining providers are active and/or locked for the user
            foreach ($configuredProviders as $provider) {
                if ($activeProviders[$provider->getIdentifier()] ?? false) {
                    $activeProviders[$provider->getIdentifier()]['providerConfiguration'] = $provider;
                }
            }

            if ($activeProviders !== []) {
                $status = '<span class="label label-success label-space-right t3js-oauth2-status-label"' . ' data-alternative-label="' . $disabledLabel . '">' . $enabledLabel . '</span>';

                // Add providers list
                $childHtml[] = '<ul class="list-group t3js-oauth2-active-providers-list">';
                foreach ($activeProviders as $identifier => $activeProvider) {
                    $childHtml[] = '<li class="list-group-item" id="provider-' . htmlspecialchars((string)$identifier, ENT_QUOTES | ENT_HTML5) . '" style="line-height: 2.1em;">';
                    $childHtml[] = $this->iconFactory->getIcon($activeProvider['providerConfiguration']->getIconIdentifier(), Icon::SIZE_SMALL);
                    $childHtml[] = htmlspecialchars($lang->sL($activeProvider['providerConfiguration']->getLabel()), ENT_QUOTES | ENT_HTML5);

                    $deleteThis = $this->uriBuilder->buildUriFromRoute(
                        'tce_db',
                        [
                            'cmd' => [
                                $oauth2ProviderConfigurationTable => [
                                    $activeProvider['uid'] => [
                                        'delete' => 1,
                                    ],
                                ],
                            ],
                            'redirect' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
                        ]
                    );
                    $childHtml[] = '<a href="' . $deleteThis . '" ';
                    $childHtml[] = ' class="btn btn-default btn-sm pull-right t3js-modal-trigger"';
                    $childHtml[] = ' data-title="' .
                                   htmlspecialchars(
                                       sprintf(
                                           $lang->sL('LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:buttons.deactivateProvider'),
                                           $lang->sL($activeProvider['providerConfiguration']->getLabel())
                                       ),
                                       ENT_QUOTES | ENT_HTML5
                                   ) .
                                   '"';
                    $childHtml[] = ' data-bs-content="' .
                                   htmlspecialchars(
                                       sprintf(
                                           $lang->sL('LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:buttons.deactivateProvider.confirmation.text'),
                                           $lang->sL($activeProvider['providerConfiguration']->getLabel())
                                       ),
                                       ENT_QUOTES | ENT_HTML5
                                   ) .
                                   '"';
                    $childHtml[] = ' data-button-close-text="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel'), ENT_QUOTES | ENT_HTML5) . '"';
                    $childHtml[] = ' data-button-ok-text="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deactivate'), ENT_QUOTES | ENT_HTML5) . '"';
                    $childHtml[] = ' data-severity="warning"';
                    $childHtml[] = ' title="' .
                                   htmlspecialchars(
                                       sprintf(
                                           $lang->sL('LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:buttons.deactivateProvider'),
                                           $lang->sL(
                                               $activeProvider['providerConfiguration']->getLabel()
                                           )
                                       ),
                                       ENT_QUOTES | ENT_HTML5
                                   ) .
                                   '"';
                    $childHtml[] = '>';
                    $childHtml[] = $this->iconFactory->getIcon('actions-delete', Icon::SIZE_SMALL)->render('inline');
                    $childHtml[] = '</a>';
                    $childHtml[] = '</li>';
                }
                $childHtml[] = '</ul>';
            }
        }
        $fieldId = 't3js-form-field-oauth2-id' . StringUtility::getUniqueId('-');

        $html[] = '<div class="formengine-field-item t3js-formengine-field-item" id="' . htmlspecialchars($fieldId) . '">';
        $html[] = '<div class="form-control-wrap" style="max-width: ' . (int)$this->formMaxWidth($this->defaultInputWidth) . 'px">';
        $html[] = '<div class="form-wizards-wrap">';
        $html[] = '<div class="form-wizards-element">';
        $html[] = implode(PHP_EOL, $childHtml);
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '</div>';

        $resultArray['html'] = $status . implode(PHP_EOL, $html);
        return $resultArray;
    }
}
