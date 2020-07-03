<?php

declare(strict_types=1);

namespace cwebp;

// log time
$time = hrtime(true);

// debug
cwebp::set_debug(true);

// log script max execution time
log::log(ini_get('max_execution_time'));

// check that cwebp is installed
if (cwebp::installed())
    log::log('cwebp installed - OK');
else
    log::log('cwebp installed - FAILED');

// list images to convert
$dir   = 'images';
$files = [];

if (!cwebp::list_images($dir, $files)) {
    log::log('List images - FAILED');
    exit();
}

// convert images
log::log('Convert images... - count - '. count($files));

foreach ($files as $file) {
    // check if image was already converted
    if (file_exists($file .'.webp')) {
        // compare files modification time
        $src_modified  = filemtime($file);
        $dest_modified = filemtime($file .'.webp');

        // if non webp image was modified after webp, it means the image was updated and therefore needs to be converted again
        if ($src_modified > $dest_modified) {
            log::log('Convert image - SKIPPED - '. $file);
            continue;
        }
    }

    // convert single image
    if (cwebp::convert_image($file))
        log::log('Convert image - OK - '. $file);
    else
        log::log('Convert image - FAILED - '. $file);
}

log::log('Convert images - OK');

cwebp::stats();

$delta_time = round((hrtime(true) - $time) / 1e+6, 0);
log::log("total time - {$delta_time}ms");

class cwebp
{
    /**
     * Check that cwebp is installed
     * @return bool true if installed, otherwise false
     */
    public static function installed(): bool
    {
        return self::command_exists('cwebp');
    }

    /**
     * List images in directory
     * @param string $dir
     * @param [out] array $files
     * @return true on success, otherwise false
     */
    public static function list_images(string $dir, array& $files): bool
    {
        // list all files
        if (!self::list_dir($dir, $files)) {
            $files = null;
            return false;
        }

        // filter to only images
        $files = array_filter($files, 'self::filter_images');

        return true;
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
        $command = "cwebp '{$src}' -o '{$dest}'";

        if (self::$debug) {
            $time = hrtime(true);
            log::log($command);
        }

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
            log::log('webp image bigger than source');
        }

        $delta_time = hrtime(true) - $time;
        $delta_time /= 1e+6;
        $delta_time = round($delta_time, 0);

        self::$stat_time += $delta_time;

        if (self::$debug) {
            $src_size  = self::format_size($src_size, 0);
            $dest_size = self::format_size($dest_size, 0);
            $delta     = self::format_size($delta, 0);

            log::log("delta - $delta_per% / $delta - ${delta_time}ms - src size - ${src_size} - dest size - ${dest_size}");
        }

        return true;
    }

    /**
     * Set debug
     * @param bool $value
     * @return void
     */
    public static function set_debug(bool $value): void
    {
        self::$debug = $value;
    }

    /**
     * Print stats
     * @return void
     */
    public static function stats(): void
    {
        $delta = self::$stat_dest_size - self::$stat_src_size;

        $delta = self::format_size($delta, 0);

        $stat_src_size  = self::format_size(self::$stat_src_size, 0);
        $stat_dest_size = self::format_size(self::$stat_dest_size, 0);
        $stat_time      = self::$stat_time;

        log::log("delta size - {$delta} - src size - {$stat_src_size} - dest size - {$stat_dest_size}");
        log::log("total time - {$stat_time}ms");
    }

    private static $images_ext = [
        'jpg',
        'jpeg',
        'png',
    ];

    private static $debug     = false;

    private static $stat_src_size  = 0;
    private static $stat_dest_size = 0;
    private static $stat_time      = 0;

    /**
     * List directory recursively
     * @param string $dir
     * @param [out] array $files
     * @return true on success, otherwise false
     */
    private static function list_dir(string $dir, array& $files): bool
    {
        // list directory
        $list = scandir($dir);

        // check for errors
        if (!$list) {
            $files = null;
            return false;
        }

        // list subdirectories
        foreach ($list as $file) {
            // ignore . and .. directories
            if (in_array($file, ['.','..']))
                continue;

            // check if directory
            if (!is_dir($dir . DIRECTORY_SEPARATOR . $file))
                // add file
                $files[] = $dir . DIRECTORY_SEPARATOR. $file;
            else
                // add directory
                self::list_dir($dir . DIRECTORY_SEPARATOR . $file, $files);
        }

        return true;
    }

    /**
     * Check if command is installed
     * @param  string $cmd
     * @return bool true if installed, otherwise false
     */
    private static function command_exists(string $cmd): bool
    {
        $return = shell_exec(sprintf("which %s", escapeshellarg($cmd)));
        return !empty($return);
    }

    private static function format_size($bytes, $precision = 2)
    {
        $kilobyte = 1024;
        $megabyte = $kilobyte * $kilobyte;
        $gigabyte = $megabyte * $kilobyte;
        $terabyte = $gigabyte * $kilobyte;

        if ($bytes < 0) {
            $neg   = true;
            $bytes = -$bytes;
        }
        else
            $neg = false;

        if (($bytes >= 0) && ($bytes < $kilobyte))
            $result = $bytes .'B';
        elseif (($bytes >= $kilobyte) && ($bytes < $megabyte))
            $result = round($bytes / $kilobyte, $precision) .'KB';
        elseif (($bytes >= $megabyte) && ($bytes < $gigabyte))
            $result = round($bytes / $megabyte, $precision) .'MB';
        elseif (($bytes >= $gigabyte) && ($bytes < $terabyte))
            $result = round($bytes / $gigabyte, $precision) .'GB';
        elseif ($bytes >= $terabyte)
            $result = round($bytes / $terabyte, $precision) .'TB';
        else
            $result = $bytes .'B';

        return $neg ? '-'. $result : $result;
    }

    /**
     * Filter files that are images
     * @param  string $file
     * @return bool true if image otherwise false
     */
    private static function filter_images(string $file): bool
    {
        // get file extension
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        if (in_array($ext, self::$images_ext))
            return true;
        else
            return false;
    }
}

class log
{
    private static $init = false;

    public static function log(string $line): void
    {
        if (!self::$init) {
            self::$init = true;
            echo('<pre>'. PHP_EOL);
        }

        echo($line . PHP_EOL);
    }

    public static function line(): void
    {
        self::log('');
    }
}

