<?php

use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(static function () {
    (GeneralUtility::makeInstance(Registry::class))->configureContainer(
        (new ContainerConfiguration(
            '2',
            '',
            'Some Description of the Container',
            [
                [['name' => 'TwoColumn (left)', 'colPos' => 10],['name' => 'TwoColumn (right)', 'colPos' => 20],],

            ],
        ))
    );
});
