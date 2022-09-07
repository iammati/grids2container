<?php

use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

(static function () {
    (GeneralUtility::makeInstance(Registry::class))->configureContainer(
        (new ContainerConfiguration(
            '2col',
            '2 Column',
            'Some Description of the Container',
            [
                [
                    ['name' => '2-cols-left', 'colPos' => 401],
                    ['name' => '2-cols-right', 'colPos' => 402]
                ],
            ],
        ))
    );
})();
