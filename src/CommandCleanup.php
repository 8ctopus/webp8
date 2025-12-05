<?php

declare(strict_types=1);

/**
 * @author 8ctopus <hello@octopuslabs.io>
 */

namespace Oct8pus\Webp;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommandCleanup extends Command
{
    /**
     * Configure command options
     *
     * @return void
     */
    protected function configure() : void
    {
        $this->setName('cleanup')
            ->setDescription('Delete all webp images from directory')
            ->addArgument('directory', InputArgument::REQUIRED)
            ->addOption('dry-run');
    }

    /**
     * Execute command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        // beautify input, output interface
        $io = new SymfonyStyle($input, $output);

        $dir = $input->getArgument('directory');

        $dir = realpath($dir);

        if (!$dir) {
            $io->error('Directory does not exist');

            return 1;
        }

        $files = [];

        $files = Helper::listDirExtension($dir, ['.webp']);

        $count = count($files);

        if (!$count) {
            $io->success('It\'s already clean');

            return 0;
        }

        if (!$input->getOption('dry-run') && !$io->confirm("sure you want to delete {$count} webp images?", false)) {
            $io->warning('Abort');

            return 0;
        }

        foreach ($files as $file) {
            if (!$input->getOption('dry-run')) {
                unlink($file);
            }

            $io->writeln("Deleted {$file}", OutputInterface::VERBOSITY_VERBOSE);
        }

        $io->success('');

        return 0;
    }
}
