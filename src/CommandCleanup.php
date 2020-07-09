<?php declare(strict_types=1);

/**
 * @author 8ctopus <hello@octopuslabs.io>
 */

namespace Oct8pus\Webp;

use Oct8pus\Webp\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class CommandCleanup extends Command
{
    /**
     * Configure command options
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('cleanup')
            ->setDescription('Delete all webp images from directory')
            ->addArgument('directory', InputArgument::REQUIRED)
            ->addOption('dry-run');
    }

    /**
     * Execute command
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // beautify input, output interface
        $io = new SymfonyStyle($input, $output);

        // log time
        $stopwatch = new Stopwatch();
        $stopwatch->start('main');

        // list images to delete
        $dir   = $input->getArgument('directory');
        $files = [];

        if (!Helper::list_dir_ext($dir, Helper::$ext_webp, $files)) {
            $io->error('List images');
            exit();
        }

        // check if any images to delete
        if (!count($files)) {
            $io->warning('Nothing to cleanup');
            exit();
        }

        // delete images
        $io->writeln('<info>Delete images... - '. count($files) .'</info>');

        // ask user confirmation if not dry run
        if (!$input->getOption('dry-run') && !$io->confirm('Cleanup directory?', false)) {
            $io->warning('Abort');
            exit();
        }

        foreach ($files as $file) {
            if (!$input->getOption('dry-run'))
                // delete file
                unlink($file);

            $io->writeln("Deleted {$file}");
        }

        $io->success('Cleanup');

        // log performance
        $event = $stopwatch->stop('main');
        $io->writeln($event);

        return 0;
    }
}
