<?php

declare(strict_types=1);

namespace Oct8pus\Webp;

use Exception;

class Helper
{
    /**
     * List directory files recursively
     *
     * @param string $dir
     *
     * @return array $files
     */
    public static function listDir(string $dir) : array
    {
        if (!file_exists($dir)) {
            throw new Exception("dir does not exist - {$dir}");
        }

        $list = scandir($dir);

        if ($list === false) {
            throw new Exception("scan dir - {$dir}");
        }

        $files = [];

        // list subdirectories
        foreach ($list as $file) {
            // ignore . and .. directories
            if (in_array($file, ['.', '..'], true)) {
                continue;
            }

            // check if directory
            if (!is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
                // add file
                $files[] = $dir . DIRECTORY_SEPARATOR . $file;
            } else {
                // add directory
                $files = array_merge($files, self::listDir($dir . DIRECTORY_SEPARATOR . $file));
            }
        }

        return $files;
    }

    /**
     * List directory files recursively, filter by extension
     *
     * @param string $dir
     * @param array  $extensions
     *
     * @return array $files
     */
    public static function listDirExtension(string $dir, array $extensions) : array
    {
        $files = self::listDir($dir);

        $files = array_filter($files, function ($file) use ($extensions) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            return in_array($ext, $extensions, true);
        });

        return $files;
    }

    /**
     * Check if command is installed
     *
     * @param string $cmd
     *
     * @return bool true if installed, otherwise false
     */
    public static function commandExists(string $cmd) : bool
    {
        $return = shell_exec(sprintf('which %s', escapeshellarg($cmd)));
        return !empty($return);
    }

    /**
     * Format bytes size as string
     *
     * @param int $bytes
     * @param int $precision
     *
     * @return string
     */
    public static function formatSize(int $bytes, int $precision = 2) : string
    {
        $kilobyte = 1024;
        $megabyte = $kilobyte * $kilobyte;
        $gigabyte = $megabyte * $kilobyte;
        $terabyte = $gigabyte * $kilobyte;

        if ($bytes < 0) {
            $neg = true;
            $bytes = -$bytes;
        } else {
            $neg = false;
        }

        if ($bytes < $kilobyte) {
            $result = $bytes . ' B';
        } elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
            $result = sprintf("%.{$precision}f KB", $bytes / $kilobyte, $precision);
        } elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
            $result = sprintf("%.{$precision}f MB", $bytes / $megabyte, $precision);
        } elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
            $result = sprintf("%.{$precision}f GB", $bytes / $gigabyte, $precision);
        } elseif ($bytes >= $terabyte) {
            $result = sprintf("%.{$precision}f TB", $bytes / $terabyte, $precision);
        } else {
            $result = $bytes . ' B';
        }

        return $neg ? '-' . $result : $result;
    }

    /**
     * Format milliseconds to minutes and seconds string
     *
     * @param float $ms
     *
     * @return string
     */
    public static function formatTime(float $ms) : string
    {
        $seconds = $ms / 1000;
        $minutes = $seconds / 60;
        $seconds = (int) $seconds % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
