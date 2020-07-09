<?php declare(strict_types=1);

namespace Oct8pus\Webp;

use Oct8pus\Webp\Helper;
use Symfony\Component\Console\Output\OutputInterface;

class Webp
{
    /**
     * Check that cwebp is installed
     * @return bool true if installed, otherwise false
     */
    public static function installed(): bool
    {
        return Helper::command_exists('cwebp');
    }

    /**
     * Convert image to webp
     * @param  string $src
     * @param  string $dest
     * @return bool true on success, otherwise false
     */
    public static function convert_image(string $src, string $dest = ''): bool
    {
        // create destination file
        if (empty($dest))
            $dest = "{$src}.webp";

        // create command
        // https://developers.google.com/speed/webp/docs/cwebp
        $command = "cwebp '{$src}' -o '{$dest}' -quiet -m 6";

        self::log($command);

        // log time
        $time = hrtime(true);

        // convert image
        exec($command, $output, $status);

        // check command return code
        if ($status != 0)
            return false;

        // compare image sizes
        $src_size  = filesize($src);
        $dest_size = filesize($dest);

        // save sizes
        self::$stat_src_size  += $src_size;
        self::$stat_dest_size += $dest_size;

        $delta     = $dest_size - $src_size;
        $delta_per = round($delta * 100 / $src_size, 0);

        if ($delta > 0) {
            // TODO - delete image?
            self::log('<comment>webp image bigger than source</comment>');
        }

        $delta_time = hrtime(true) - $time;
        $delta_time /= 1e+6;
        $delta_time = round($delta_time, 0);

        self::$stat_time += $delta_time;

        $src_size  = Helper::format_size($src_size, 0);
        $dest_size = Helper::format_size($dest_size, 0);
        $delta     = Helper::format_size($delta, 0);

        self::log("delta - $delta_per% / $delta - ${delta_time}ms - src size - ${src_size} - dest size - ${dest_size}");

        return true;
    }

    /**
     * Get stats
     * @param [out] $src_size
     * @param [out] $dest_size
     * @param [out] $time
     * @return void
     */
    public static function stats(&$src_size, &$dest_size, &$time): void
    {
        $src_size  = self::$stat_src_size;
        $dest_size = self::$stat_dest_size;
        $time      = self::$stat_time;
    }

    /**
     * Set logger
     * @param OutputInterface $output
     * @return void
     */
    public static function set_logger(OutputInterface $output): void
    {
        self::$logger = $output;
    }

    /**
     * Log message
     * @param  string $message
     * @return void
     */
    private static function log(string $message): void
    {
        if (!self::$logger)
            return;

        self::$logger->writeln($message);
    }

    private static $stat_src_size  = 0;
    private static $stat_dest_size = 0;
    private static $stat_time      = 0;
    private static $logger         = null;
}
