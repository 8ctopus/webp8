<?php

declare(strict_types=1);

namespace Oct8pus\Webp;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class CommandConvert extends Command
{
    private $io;
    private $bar;

    /**
     * Configure command options
     *
     * @return void
     */
    protected function configure() : void
    {
        $this->setName('convert')
            ->setDescription('Convert images in directory to webp')
            ->addArgument('directory', InputArgument::REQUIRED)
            ->addOption('cwebp_m', 'M', InputOption::VALUE_OPTIONAL, 'Specify the compression method to use (0 - 6)', null)
            ->addOption('cwebp_q', 'Q', InputOption::VALUE_OPTIONAL, 'Specify the compression factor for RGB channels between 0 and 100', null)
            ->addOption('cwebp_z', 'Z', InputOption::VALUE_OPTIONAL, 'Switch on lossless compression mode with the specified level between 0 and 9', null)
            ->addOption('multithreading', 'm', InputOption::VALUE_NONE, 'use multi-threading to convert files');
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
        $this->io = new SymfonyStyle($input, $output);

        // log performance
        $stopwatch = new Stopwatch();
        $stopwatch->start('main');

        // log script max execution time
        $this->io->writeln('max_execution_time - ' . ini_get('max_execution_time'), OutputInterface::VERBOSITY_VERBOSE);

        // check that cwebp is installed
        if (Helper::commandExists('cwebp')) {
            $this->io->writeln('cwebp command found', OutputInterface::VERBOSITY_VERBOSE);
        } else {
            $this->io->error([
                'cwebp command is missing',
                'ubuntu: apt install webp',
                'alpine: apk add libwebp-tools',
                'windows: download libwebp and extract cwebp.exe, add cwebp.exe to PATH (Environment Variables)',
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

        $extensions = [
            'jpg',
            'jpeg',
            'png',
        ];

        $files = Helper::listDirExtension($dir, $extensions);

        $stats = [
            'size_src' => 0,
            'size_dest' => 0,
            'skipped' => 0,
            'webp_bigger' => 0,
            'webp_zero_size' => 0,
        ];

        $this->createProgressBar(count($files));

        $multithreading = $input->getOption('multithreading');

        // quality factor (0:small..100:big), default=75
        $q = $input->getOption('cwebp_q') === null ? null : (int) $input->getOption('cwebp_q');

        // compression method (0=fast, 6=slowest), default=4
        $m = $input->getOption('cwebp_m') === null ? null : (int) $input->getOption('cwebp_m');

        // activates lossless preset with level in [0:fast, ..., 9:slowest]
        $z = $input->getOption('cwebp_z') === null ? null : (int) $input->getOption('cwebp_z');

        foreach ($files as $file) {
            // check if image was already converted
            if (file_exists($file . '.webp')) {
                // compare files modification time
                $srcModified = filemtime($file);
                $destModified = filemtime($file . '.webp');

                // if source image was modified after webp, it means the image was updated and therefore needs to be converted again
                if ($srcModified < $destModified) {
                    $this->io->writeln('Skip webp exists - ' . $file, OutputInterface::VERBOSITY_VERBOSE);
                    ++$stats['skipped'];

                    // advance progress bar
                    $this->progressBarAdvance();

                    continue;
                }
            }

            // convert single image to webp
            if (self::convert($file, $stats, $multithreading, '', $q, $m, $z)) {
                $this->io->writeln('Convert image - ' . $file, OutputInterface::VERBOSITY_VERBOSE);
            } else {
                $this->io->error('Convert image - ' . $file);
            }

            // advance progress bar
            $this->progressBarAdvance();
        }

        // log success
        $this->io->newLine(2);
        $this->io->success('');

        // check performance
        $event = $stopwatch->stop('main');
        $time = Helper::formatTime($event->getDuration());

        // calculate stats
        //$size_delta = Helper::format_size($stats['size_dest'] - $stats['size_src'], 1);

        $compression = round($stats['size_src'] / ($stats['size_dest'] ? $stats['size_dest'] : 1), 1) . ' x';

        $size_src = Helper::formatSize($stats['size_src'], 1);
        $size_dest = Helper::formatSize($stats['size_dest'], 1);

        // create table
        $this->io->table([
            'total',
            'converted',
            'skipped',
            'webp bigger',
            'time',
            'size original',
            'size webp',
            'compression',
            'webp zero size',
        ], [
            [
                count($files),
                count($files) - $stats['skipped'],
                $stats['skipped'],
                $stats['webp_bigger'],
                $time,
                $size_src,
                $size_dest,
                $compression,
                $stats['webp_zero_size'],
            ],
        ]);

        return 0;
    }

    /**
     * Convert image to webp
     *
     * @param string $src
     * @param  [in, out] array $stats
     * @param bool   $multithreading
     * @param string $dest
     * @param int    $q              cwebp -q
     * @param int    $m              cwebp -m
     * @param int    $z              cwebp -z
     *
     * @return bool true on success, otherwise false
     */
    private function convert(string $src, array &$stats, bool $multithreading, string $dest = '', int $q = null, int $m = null, int $z = null) : bool
    {
        // create destination file
        if (empty($dest)) {
            $dest = "{$src}.webp";
        }

        // create command
        // https://developers.google.com/speed/webp/docs/cwebp
        $options = '-quiet';

        // Default "-m 6" as previously
        if ($q === null && $m === null && $z === null) {
            $options .= ' -m 6';
        }
        // New options available
        else {
            if ($q !== null) {
                $options .= " -q {$q}";
            }

            if ($m !== null) {
                $options .= " -m {$m}";
            }

            if ($z !== null) {
                $options .= " -z {$z}";
            }
        }

        // check for multi-threading option
        if ($multithreading) {
            $options .= ' -mt';
        }

        if (strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN') {
            $command = "cwebp {$options} \"{$src}\" -o \"{$dest}\"";
        } else {
            $command = "cwebp {$options} '{$src}' -o '{$dest}'";
        }

        $this->io->writeln(PHP_EOL . $command, OutputInterface::VERBOSITY_VERBOSE);

        // log time
        $time = hrtime(true);

        // convert image
        exec($command, $output, $status);

        // check command return code
        if ($status != 0) {
            return false;
        }

        // compare image sizes
        $size_src = filesize($src);
        $size_dest = filesize($dest);

        $delta = $size_dest - $size_src;
        $delta_per = round($delta * 100 / $size_src, 0);

        if ($delta > 0) {
            // delete webp if bigger than original
            unlink($dest);
            ++$stats['webp_bigger'];
            $this->io->writeln("<comment>webp image bigger than source - deleted - {$dest}</comment>", OutputInterface::VERBOSITY_VERBOSE);
        } else {
            // save sizes
            $stats['size_src'] += $size_src;
            $stats['size_dest'] += $size_dest;
        }

        if ($size_dest <= 0) {
            // delete webp if file size zero
            unlink($dest);
            ++$stats['webp_zero_size'];
            $this->io->writeln("<comment>webp image file size zero - deleted - {$dest}</comment>", OutputInterface::VERBOSITY_VERBOSE);
        }

        // elapsed time
        $delta_time = hrtime(true) - $time;
        $delta_time /= 1e+6;
        $delta_time = Helper::formatTime($delta_time);

        // format sizes
        $size_src = Helper::formatSize($size_src, 0);
        $size_dest = Helper::formatSize($size_dest, 0);
        $delta = Helper::formatSize($delta, 0);

        // log
        $this->io->writeln("delta - {$delta_per}% / {$delta} - {$delta_time} - size src - {$size_src} - size dest - {$size_dest}", OutputInterface::VERBOSITY_VERBOSE);

        return true;
    }

    /**
     * Create progress bar
     *
     * @param int $steps
     *
     * @return void
     */
    private function createProgressBar(int $steps) : void
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

        $this->bar->start($steps);
    }

    /**
     * Advance progress bar by one step
     *
     * @return void
     */
    private function progressBarAdvance() : void
    {
        if ($this->bar) {
            $this->bar->advance();
        }
    }
}
