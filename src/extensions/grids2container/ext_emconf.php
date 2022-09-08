<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Grids to Container Migration',
    'description' => 'Migrates any GridElements to Containers from b13/container package.',
    'category' => 'misc',
    'author' => 'Mati Sediqi',
    'author_email' => 'mati_01@iclod.com',
    'state' => 'stable',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearCacheOnLoad' => true,
    'constraints' => [
        'depends' => ['typo3' => '10.4.31-10.99.99'],
        'conflicts' => [],
        'suggests' => [],
    ],
];
