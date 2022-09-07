<?php

declare(strict_types=1);

namespace Site\Grids2container\Command;

use B13\Container\Tca\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class Grids2containerMigration extends Command
{
    private TypoScriptService $typoScriptService;
    private QueryBuilder $qb;
    private ConnectionPool $connectionPool;
    private Registry $registry;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->qb = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $this->registry = GeneralUtility::makeInstance(Registry::class);
    }

    protected function configure()
    {
        $this->setHelp(
            'Prints a list of recent sys_log entries.' . LF .
                'If you want to get more detailed information, use the --verbose option.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $counter = [
            'pages' => 0,
            'grids' => 0,
            'childs' => 0,
        ];

        $io = new SymfonyStyle($input, $output);

        if ($this->getDescription()) {
            $io->title($this->getDescription());
        }

        $pids = $this->getQueryBuilder(new: true)
            ->select('uid')
            ->from('pages')
            ->where(
                $this->qb->expr()->eq('pid', 0, \PDO::PARAM_INT)
            )
            ->execute()
            ->fetchAllAssociative();

        foreach ($pids as $page) {
            $pid = (int)$page['uid'];

            $grids = $this->getQueryBuilder(new: true)
                ->select('uid', 'tx_gridelements_backend_layout')
                ->from('tt_content')
                ->where(
                    $this->qb->expr()->eq('uid', (int)$pid, \PDO::PARAM_INT),
                    $this->qb->expr()->eq('CType', $this->qb->createNamedParameter('gridelements_pi1'), \PDO::PARAM_STR),
                )
                ->execute()
                ->fetchAllAssociative();

            if (empty($grids)) {
                continue;
            }

            foreach ($grids as $grid) {
                $CType = $grid['tx_gridelements_backend_layout'];
                $containerGrid = $this->registry->getGrid($CType);

                $tsConfig = BackendUtility::getPagesTSconfig($pid)['tx_gridelements.'] ?? null;

                if ($tsConfig === null) {
                    $io->note("Skipping page with the UID #${pid} since its PageTSconf does not contain any tx_gridelements configuration.");
                    continue;
                }

                $setup = $this->typoScriptService->convertTypoScriptArrayToPlainArray($tsConfig)['setup'];

                if ($setup === null) {
                    continue;
                }

                if (empty($containerGrid)) {
                    $tcaStaticFileCacheName = (new PackageDependentCacheIdentifier(
                        GeneralUtility::makeInstance(PackageManager::class)
                    ))->withPrefix('tca_base')->toString();

                    unlink("/var/www/html/var/cache/code/core/{$tcaStaticFileCacheName}.php");

                    $this->generateByGridElementTSconf($setup);

                    ExtensionManagementUtility::loadBaseTca();
                    $containerGrid = $this->registry->getGrid($CType);
                }

                $this->getQueryBuilder(new: true)
                    ->update('tt_content')
                    ->where(
                        $this->qb->expr()->eq('uid', $grid['uid'])
                    )
                    ->set('CType', $CType)
                    ->executeStatement();

                $records = $this->getQueryBuilder(new: true)
                    ->select('uid', 'tx_gridelements_columns')
                    ->from('tt_content')
                    ->where(
                        $this->qb->expr()->eq('deleted', 0, \PDO::PARAM_INT),
                        $this->qb->expr()->eq('pid', $pid, \PDO::PARAM_INT),
                        $this->qb->expr()->eq('tx_container_parent', 0, \PDO::PARAM_INT),
                        $this->qb->expr()->eq('tx_gridelements_container', $grid['uid'], \PDO::PARAM_INT),
                    )
                    ->execute()
                    ->fetchAllAssociative();

                if (empty($records)) {
                    $io->note("No child-records found for grid with the UID #${grid['uid']}.");
                    continue;
                }

                foreach ($records as $record) {
                    $colPos = (int)$record['tx_gridelements_columns'];

                    $this->getQueryBuilder(new: true)
                        ->update('tt_content')
                        ->where(
                            $this->qb->expr()->eq('uid', $record['uid'], \PDO::PARAM_INT),
                            $this->qb->expr()->eq('tx_gridelements_columns', (int)$colPos, \PDO::PARAM_INT),
                        )
                        ->set('tx_container_parent', (int)$grid['uid'])
                        ->set('colPos', (int)$colPos)
                        ->executeStatement();

                    $counter['childs']++;
                }

                $counter['grids']++;
            }

            $counter['pages']++;
        }

        $io->writeln("  !! Done updating ${counter['pages']} pages, ${counter['grids']} grids and ${counter['childs']} child-records. !!\n");

        return Command::SUCCESS;
    }

    private function getQueryBuilder(string $tableName = 'tt_content', bool $new = false): QueryBuilder
    {
        if ($new) {
            $this->qb = $this->connectionPool->getQueryBuilderForTable($tableName);
        }

        return $this->qb;
    }

    private function generateByGridElementTSconf(array $setup)
    {
        $CType = key($setup);
        $title = $setup['title'];
        $config = &$setup[$CType]['config'];
        $rows = $config['rows'];

        $gridCfg = '';
        $typoScriptCfg = '';

        foreach ($rows as $row) {
            $gridCfg .= <<<EOT
            [
            EOT;

            foreach ($row['columns'] as $column) {
                $gridCfg .= <<<EOT
                ['name' => '{$column['name']}', 'colPos' => {$column['colPos']}],
                EOT;
            }

            $gridCfg .= <<<EOT
            ],\n
            EOT;

            $typoScriptCfg .= <<<EOT
            {$column['colPos']} = B13\Container\DataProcessing\ContainerProcessor
            {$column['colPos']} {
                colPos = {$column['colPos']}
                as = children_{$column['colPos']}
            }
            EOT;
        }

        $stream = fopen("/var/www/html/src/extensions/grids2container/Configuration/TCA/Overrides/{$CType}.php", 'w') or die('XD');
        fwrite($stream, <<<EOT
        <?php

        use B13\Container\Tca\ContainerConfiguration;
        use B13\Container\Tca\Registry;
        use TYPO3\CMS\Core\Utility\GeneralUtility;

        call_user_func(static function () {
            (GeneralUtility::makeInstance(Registry::class))->configureContainer(
                (new ContainerConfiguration(
                    '{$CType}',
                    '{$title}',
                    'Some Description of the Container',
                    [
                        {$gridCfg}
                    ],
                ))
            );
        });

        EOT);

        fclose($stream);

        $stream = fopen("/var/www/html/src/extensions/grids2container/Configuration/TypoScript/{$CType}.typoscript", 'w') or die('XD');
        fwrite($stream, <<<EOT
        tt_content.{$CType} < lib.contentElement
        tt_content.{$CType} {
            templateName = {$CType}
            templateRootPaths.10 = EXT:site_grids2container/Resources/Private/Templates/Containers
            dataProcessing {
                {$typoScriptCfg}
            }
        }

        EOT);

        fclose($stream);
    }
}
