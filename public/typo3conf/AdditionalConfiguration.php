<?php

// $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'] = \Site\Dev\Error\DebugExceptionHandler::class;

// if (isset($_GET['debug']) && (int)$_GET['debug'] === 1) {
//     $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'] = \Site\Dev\Error\DebugExceptionHandler::class;
// }

\B13\Config::initialize()
    ->appendContextToSiteName()
    ->useGraphicsMagick()
    ->includeContextDependentConfigurationFiles()
;
