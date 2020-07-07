<?php declare(strict_types=1);

namespace Oct8pus\Webp;

class Helper
{
    /**
     * List directory recursively
     * @param string $dir
     * @param [out] array $files
     * @return true on success, otherwise false
     */
    public static function list_dir(string $dir, array& $files): bool
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

    public static $images_ext = [
        'jpg',
        'jpeg',
        'png',
    ];

    /**
     * Filter files that are images
     * @param  string $file
     * @return bool true if image otherwise false
     */
    public static function filter_images(string $file): bool
    {
        // get file extension
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        if (in_array($ext, self::$images_ext))
            return true;
        else
            return false;
    }

    /**
     * Check if command is installed
     * @param  string $cmd
     * @return bool true if installed, otherwise false
     */
    public static function command_exists(string $cmd): bool
    {
        $return = shell_exec(sprintf("which %s", escapeshellarg($cmd)));
        return !empty($return);
    }

    /**
     * Format bytes size as string
     * @param  int $bytes
     * @param  int $precision
     * @return string
     */
    public static function format_size(int $bytes, int $precision = 2): string
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
}
