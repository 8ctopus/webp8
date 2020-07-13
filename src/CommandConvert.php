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

class CommandConvert extends Command
{
    private $io;
    private $bar;

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
        // beautify input, output interface
        $this->io = new SymfonyStyle($input, $output);

        // log performance
        $stopwatch = new Stopwatch();
        $stopwatch->start('main');

        // log script max execution time
        $this->io->writeln('max_execution_time - '. ini_get('max_execution_time'), OutputInterface::VERBOSITY_VERBOSE);

        // check that cwebp is installed
        if (Helper::command_exists('cwebp'))
            $this->io->writeln('cwebp command found', OutputInterface::VERBOSITY_VERBOSE);
        else {
            $this->io->error([
                'cwebp command is missing',
                'ubuntu: apt install webp',
                'alpine: apk add libwebp-tools'
            ]);

            return 127;
        }

        // get directory argument
        $dir = $input->getArgument('directory');

        // convert to realpath
        $dir = realpath($dir);

        if (!$dir) {
            $this->io->error('Directory does not exist');

            return 1;
        }

        // list images to convert
        $files = [];

        if (!Helper::list_dir_ext($dir, Helper::$ext_jpg_png, $files)) {
            $this->io->error('List images');

            return 1;
        }

        $stats = [
            'size_src'    => 0,
            'size_dest'   => 0,
            'skipped'     => 0,
            'webp_bigger' => 0,
        ];

        // create progress bar
        $this->createProgressBar(count($files));

        foreach ($files as $i => $file) {
            // check if image was already converted
            if (file_exists($file .'.webp')) {
                // compare files modification time
                $src_modified  = filemtime($file);
                $dest_modified = filemtime($file .'.webp');

                // if source image was modified after webp, it means the image was updated and therefore needs to be converted again
                if ($src_modified < $dest_modified) {
                    $this->io->writeln('Skip webp exists - '. $file, OutputInterface::VERBOSITY_VERBOSE);
                    $stats['skipped'] += 1;

                    // advance progress bar
                    $this->progressBarAdvance();

                    continue;
                }
            }

            // convert single image to webp
            if (self::convert($file, $stats))
                $this->io->writeln('Image converted - '. $file, OutputInterface::VERBOSITY_VERBOSE);
            else
                $this->io->error('Convert image - '. $file);

            // advance progress bar
            $this->progressBarAdvance();
        }

        // log success
        $this->io->newLine(2);
        $this->io->success('');

        // check performance
        $event = $stopwatch->stop('main');
        $time  = Helper::format_time($event->getDuration());

        // calculate stats
        $size_delta = Helper::format_size($stats['size_dest'] - $stats['size_src'], 1);
        $size_src   = Helper::format_size($stats['size_src'], 1);
        $size_dest  = Helper::format_size($stats['size_dest'], 1);

        // create table
        $this->io->table([
            'total',
            'converted',
            'skipped',
            'webp bigger',
            'time',
            'size original',
            'size webp',
        ], [[
                count($files),
                count($files) - $stats['skipped'],
                $stats['skipped'],
                $stats['webp_bigger'],
                $time,
                $size_src,
                $size_dest,
            ],
        ]);

        return 0;
    }

    /**
     * Convert image to webp
     * @param  string $src
     * @param  [in, out] array $stats
     * @param  string $dest
     * @return bool true on success, otherwise false
     */
    private function convert(string $src, array &$stats, string $dest = ''): bool
    {
        // create destination file
        if (empty($dest))
            $dest = "{$src}.webp";

        // create command
        // https://developers.google.com/speed/webp/docs/cwebp
        $command = "cwebp '{$src}' -o '{$dest}' -quiet -m 6";

        $this->io->writeln(PHP_EOL . $command, OutputInterface::VERBOSITY_VERBOSE);

        // log time
        $time = hrtime(true);

        // convert image
        exec($command, $output, $status);

        // check command return code
        if ($status != 0)
            return false;

        // compare image sizes
        $size_src  = filesize($src);
        $size_dest = filesize($dest);

        $delta     = $size_dest - $size_src;
        $delta_per = round($delta * 100 / $size_src, 0);

        if ($delta > 0) {
            // delete webp if bigger than original
            unlink($dest);
            $stats['webp_bigger'] += 1;
            $this->io->writeln("<comment>webp image bigger than source - deleted - {$dest}</comment>", OutputInterface::VERBOSITY_VERBOSE);
        }
        else {
            // save sizes
            $stats['size_src']  += $size_src;
            $stats['size_dest'] += $size_dest;
        }

        // elapsed time
        $delta_time  = hrtime(true) - $time;
        $delta_time /= 1e+6;
        $delta_time  = Helper::format_time($delta_time);

        // format sizes
        $size_src  = Helper::format_size($size_src, 0);
        $size_dest = Helper::format_size($size_dest, 0);
        $delta     = Helper::format_size($delta, 0);

        // log
        $this->io->writeln("delta - $delta_per% / $delta - ${delta_time} - size src - ${size_src} - size dest - ${size_dest}", OutputInterface::VERBOSITY_VERBOSE);

        return true;
    }

    /**
     * Create progress bar
     * @param int $steps
     * @return void
     */
    private function createProgressBar(int $steps): void
    {
        // do not show progress bar in verbose mode
        if ($this->io->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $this->bar = null;
            return;
        }

        $this->io->newLine();

        $this->bar = $this->io->createProgressBar($steps);

        $this->bar->setBarWidth(70);
        $this->bar->setFormat(' [%bar%] %current%/%max% (%percent:3s%%) - %elapsed:6s%/%estimated:-6s% - %memory:6s%');

        $this->bar->start();
    }

    /**
     * Advance progress bar by one step
     * @return void
     */
    private function progressBarAdvance(): void
    {
        if ($this->bar)
            $this->bar->advance();
    }
}
