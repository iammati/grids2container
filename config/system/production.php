<?php

\B13\Config::get()
    ->useGraphicsMagick()
    ->initializeDatabaseConnection([
        'dbname' => getenv('TYPO3_DB_DATABASE'),
        'host' => getenv('TYPO3_DB_HOST'),
        'password' => getenv('TYPO3_DB_PASSWORD'),
        'user' => getenv('TYPO3_DB_USERNAME'),
    ])
;
