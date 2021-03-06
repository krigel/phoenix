<?php

namespace Phoenix\Command;

use Phoenix\Migration\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RollbackCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('rollback')
            ->setDescription('Rollback all available migrations');
        
        parent::configure();
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $migrations = $this->manager->findMigrationsToExecute(Manager::TYPE_DOWN, Manager::TARGET_FIRST);
        if (empty($migrations)) {
            $output->writeln('');
            $output->writeln('<info>Nothing to rollback</info>');
            $output->writeln('');
            return;
        }
        
        foreach ($migrations as $migration) {
            $migration->rollback();
            $this->manager->removeExecution($migration);
            
            $output->writeln('');
            $output->writeln('<info>Rollback for migration ' . $migration->getClassName() . ' executed</info>');
            $output->writeln('Executed queries:', OutputInterface::VERBOSITY_DEBUG);
            $output->writeln($migration->getExecutedQueries(), OutputInterface::VERBOSITY_DEBUG);
            $output->writeln('');
        }
    }
}
