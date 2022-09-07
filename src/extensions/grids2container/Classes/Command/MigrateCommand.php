<?php

declare(strict_types=1);

namespace Site\Grids2container\Command;

use B13\Container\Tca\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class MigrateCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setHelp(
            'Prints a list of recent sys_log entries.' . LF .
                'If you want to get more detailed information, use the --verbose option.'
        );
    }

    /**
     * Executes the command for showing sys_log entries
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $pid = 1;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $grids = $queryBuilder
            ->select('uid', 'tx_gridelements_backend_layout')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid', $pid, \PDO::PARAM_INT),
                $queryBuilder->expr()->eq('deleted', 0, \PDO::PARAM_INT),
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('gridelements_pi1'), \PDO::PARAM_STR),
            )
            ->execute()
            ->fetchAllAssociative();

        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');

        foreach ($grids as $grid) {
            $CType = $grid['tx_gridelements_backend_layout'];
            $containerGrids = $registry->getGrid($CType)[0];

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
            }
        }

        $io->writeln("AYO\n");

        return Command::SUCCESS;
    }
}
