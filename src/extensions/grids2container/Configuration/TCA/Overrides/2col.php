<?php

use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(static function () {
    (GeneralUtility::makeInstance(Registry::class))->configureContainer(
        (new ContainerConfiguration(
            '2col',
            '',
            'Some Description of the Container',
            [
                [['name' => 'Links', 'colPos' => 401],['name' => 'Rechts', 'colPos' => 402],],

            ],
        ))
    );
});
