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
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class Grids2containerMigration extends Command
{
    private QueryBuilder $qb;
    private ConnectionPool $connectionPool;
    private Registry $registry;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
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
        $pid = 1;

        $counter = [
            'grids' => 0,
            'childs' => 0,
        ];

        $io = new SymfonyStyle($input, $output);

        if ($this->getDescription()) {
            $io->title($this->getDescription());
        }

        // $outputStyle = new OutputFormatterStyle('#FFF', '#7E93C1', ['bold', 'blink']);
        // $output->getFormatter()->setStyle('info', $outputStyle);

        // $output->writeln('<info>foo</>');

        $grids = $this->qb
            ->select('uid', 'tx_gridelements_backend_layout')
            ->from('tt_content')
            ->where(
                $this->qb->expr()->eq('pid', $pid, \PDO::PARAM_INT),
                $this->qb->expr()->eq('deleted', 0, \PDO::PARAM_INT),
                $this->qb->expr()->eq('CType', $this->qb->createNamedParameter('gridelements_pi1'), \PDO::PARAM_STR),
            )
            ->execute()
            ->fetchAllAssociative();

        if (empty($grids)) {
            $io->warning('Sorry. I was not able to find any grid. Enjoy your day.');

            return Command::FAILURE;
        }

        $qb = $this->getQueryBuilder('tt_content');

        foreach ($grids as $grid) {
            $CType = $grid['tx_gridelements_backend_layout'];
            $containerGrids = $this->registry->getGrid($CType)[0];

            if ($containerGrids === null) {
                $rootLine = BackendUtility::BEgetRootLine(1);

                foreach ($rootLine as $page) {
                    $uid = (int)$page['uid'];
                    $tsConfig = BackendUtility::getPagesTSconfig($uid);
                    dump(array_keys($tsConfig));
                }

                dd('===');

                $io->error("The grid with the UID #${grid['uid']} and CType \"{$CType}\" has not been configured with b13/container TCA yet!");

                return Command::FAILURE;
            }

            $qb
                ->update('tt_content')
                ->where(
                    $qb->expr()->eq('uid', $grid['uid'])
                )
                ->set('CType', $CType)
                ->executeStatement();

            $childs = $qb
                ->select('uid', 'tx_gridelements_columns')
                ->from('tt_content')
                ->where(
                    $qb->expr()->eq('deleted', 0, \PDO::PARAM_INT),
                    $qb->expr()->eq('pid', $pid, \PDO::PARAM_INT),
                    $qb->expr()->eq('tx_container_parent', 0, \PDO::PARAM_INT),
                    $qb->expr()->eq('tx_gridelements_container', $grid['uid'], \PDO::PARAM_INT),
                )
                ->execute()
                ->fetchAllAssociative();

            if (empty($childs)) {
                $io->warning("Skipping the grid with the UID #${grid['uid']}.");
                continue;
            }

            foreach ($childs as $child) {
                foreach ($containerGrids as $col) {
                    $colPos = $col['colPos'];

                    $qb
                        ->update('tt_content')
                        ->where(
                            $qb->expr()->eq('uid', $child['uid'], \PDO::PARAM_INT),
                            $qb->expr()->eq('deleted', 0, \PDO::PARAM_INT),
                            $qb->expr()->eq('tx_gridelements_columns', (int)$child['tx_gridelements_columns'], \PDO::PARAM_INT),
                        )
                        ->set('tx_container_parent', (int)$grid['uid'])
                        ->set('colPos', (int)$colPos)
                        ->executeStatement();
                }

                $counter['childs']++;
            }

            $counter['grids']++;
        }

        $io->writeln("  !! Done updating ${counter['grids']} grids and ${counter['childs']} child-records. !!\n");

        return Command::SUCCESS;
    }

    private function getQueryBuilder(string $tableName = 'tt_content', bool $new = false): QueryBuilder
    {
        if ($new) {
            $this->qb = $this->connectionPool->getQueryBuilderForTable($tableName);
        }

        return $this->qb;
    }
}
