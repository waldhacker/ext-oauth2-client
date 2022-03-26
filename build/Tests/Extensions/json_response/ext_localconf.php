<?php

defined('TYPO3') || die();

(static function () {
    require_once(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:json_response/Resources/Private/PHP/Mocks/Backend/Controller/HeaderTestFunctions.php'));
    require_once(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:json_response/Resources/Private/PHP/Mocks/Core/Authentication/HeaderTestFunctions.php'));
    require_once(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:json_response/Resources/Private/PHP/Mocks/Core/Http/HeaderTestFunctions.php'));
    require_once(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:json_response/Resources/Private/PHP/Mocks/Core/Utility/HeaderTestFunctions.php'));
    require_once(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:json_response/Resources/Private/PHP/Mocks/Extbase/Core/HeaderTestFunctions.php'));
    require_once(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:json_response/Resources/Private/PHP/Mocks/Extbase/Mvc/HeaderTestFunctions.php'));
    require_once(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:json_response/Resources/Private/PHP/Mocks/Install/Service/HeaderTestFunctions.php'));
    require_once(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:json_response/Resources/Private/PHP/Mocks/Oauth2Client/Session/HeaderTestFunctions.php'));
})();
