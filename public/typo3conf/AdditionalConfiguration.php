<?php

\B13\Config::initialize()
    ->appendContextToSiteName()
    ->useGraphicsMagick()
    ->includeContextDependentConfigurationFiles()
;


$GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandlerErrors'] = 22517;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['syslogErrorReporting'] = 22517;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['belogErrorReporting'] = 22517;
