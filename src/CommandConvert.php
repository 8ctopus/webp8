<?php declare(strict_types=1);

/**
 * @author 8ctopus <hello@octopuslabs.io>
 */

namespace Oct8pus\Webp;

use Oct8pus\Webp\Webp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class CommandConvert extends Command
{
    /**
     * Configure command options
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('convert')
            ->setDescription('Convert images in directory to webp')
            ->addArgument('directory', InputArgument::REQUIRED);
    }

    /**
     * Execute command
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Webp::set_logger($output);

        // beautify input, output interface
        $io = new SymfonyStyle($input, $output);

        // log performance
        $stopwatch = new Stopwatch();
        $stopwatch->start('main');

        // log script max execution time
        //$io->writeln('php max execution time - '. ini_get('max_execution_time'));

        // check that cwebp is installed
        if (Webp::installed()) {
            //$io->writeln('<info>cwebp command found</info>');
        }
        else {
            $io->error([
                'cwebp command is missing',
                'ubuntu: apt install webp',
                'alpine: apk add libwebp-tools'
            ]);

            return 127;
        }

        // list images to convert
        $dir   = $input->getArgument('directory');
        $files = [];

        if (!Helper::list_dir_ext($dir, Helper::$ext_jpg_png, $files)) {
            $io->error('List images');

            return 1;
        }

        // convert images
        $io->writeln('<info>Convert images... - '. count($files) .'</info>');

        foreach ($files as $file) {
            // check if image was already converted
            if (file_exists($file .'.webp')) {
                // compare files modification time
                $src_modified  = filemtime($file);
                $dest_modified = filemtime($file .'.webp');

                // if source image was modified after webp, it means the image was updated and therefore needs to be converted again
                if ($src_modified < $dest_modified) {
                    $io->writeln('Convert image - SKIPPED - '. $file);
                    continue;
                }
            }

            // convert single image to webp
            if (Webp::convert_image($file))
                $io->writeln('Convert image - OK - '. $file);
            else
                $io->error('Convert image - '. $file);
        }

        // print stats
        Webp::stats();

        // check performance
        $event = $stopwatch->stop('main');

        // log success
        $io->success($event);

        return 0;
    }
}
