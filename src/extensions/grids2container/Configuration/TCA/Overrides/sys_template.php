<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

call_user_func(static function () {
    ExtensionManagementUtility::addStaticFile(
        'site_grids2container',
        'Configuration/TypoScript',
        'Grids2container'
    );
    ExtensionManagementUtility::addTypoScriptConstants(
        '@import "EXT:site_grids2container/Configuration/TypoScript/constants.typoscript"'
    );
});
