<?php

/**
 * Class for handling directories
 *
 * @author gharlan
 * @package redaxo\core
 */
class rex_dir
{
    /**
     * Creates a directory
     *
     * @param string  $dir       Path of the new directory
     * @param boolean $recursive When FALSE, nested directories won't be created
     * @return boolean TRUE on success, FALSE on failure
     */
    public static function create($dir, $recursive = true)
    {
        if (is_dir($dir))
            return true;

        $parent = dirname($dir);
        if (!is_dir($parent) && (!$recursive || !self::create($parent)))
            return false;

        if (self::isWritable($parent) && mkdir($dir, rex::getDirPerm())) {
            @chmod($dir, rex::getDirPerm());
            return true;
        }

        return false;
    }

    /**
     * Returns wether the directory is writable
     *
     * @param string $dir Path of the directory
     * @return boolean
     */
    public static function isWritable($dir)
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        return @is_dir($dir) && @is_writable($dir . DIRECTORY_SEPARATOR . '.');
    }

    /**
     * Copies a directory
     *
     * @param string $srcdir Path of the source directory
     * @param string $dstdir Path of the destination directory
     * @return boolean TRUE on success, FALSE on failure
     */
    public static function copy($srcdir, $dstdir)
    {
        $srcdir = rtrim($srcdir, DIRECTORY_SEPARATOR);
        $dstdir = rtrim($dstdir, DIRECTORY_SEPARATOR);

        if (!self::create($dstdir)) {
            return false;
        }

        $state = true;

        foreach (rex_finder::factory($srcdir)->recursive() as $srcfilepath => $srcfile) {
            $dstfile = $dstdir . substr($srcfilepath, strlen($srcdir));
            if ($srcfile->isDir()) {
                $state = self::create($dstfile) && $state;
            } else {
                $state = rex_file::copy($srcfilepath, $dstfile) && $state;
            }
        }

        return $state;
    }

    /**
     * Deletes a directory
     *
     * @param string  $dir        Path of the directory
     * @param boolean $deleteSelf When FALSE, only subdirectories and files will be deleted
     * @return boolean TRUE on success, FALSE on failure
     */
    public static function delete($dir, $deleteSelf = true)
    {
        return !is_dir($dir) || self::deleteIterator(rex_finder::factory($dir)->recursive()->childFirst()->ignoreSystemStuff(false)) && (!$deleteSelf || rmdir($dir));
    }

    /**
     * Deletes the files in a directory
     *
     * @param string  $dir       Path of the directory
     * @param boolean $recursive When FALSE, files in subdirectories won't be deleted
     * @return boolean TRUE on success, FALSE on failure
     */
    public static function deleteFiles($dir, $recursive = true)
    {
        $iterator = rex_finder::factory($dir)->recursive($recursive)->filesOnly()->ignoreSystemStuff(false);
        return self::deleteIterator($iterator);
    }

    /**
     * Deletes files and directories by a rex_dir_iterator
     *
     * @param Traversable $iterator Iterator, $iterator->current() must return a SplFileInfo-Object
     * @return boolean TRUE on success, FALSE on failure
     */
    public static function deleteIterator(Traversable $iterator)
    {
        $state = true;

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $state = rmdir($file) && $state;
            } else {
                $state = rex_file::delete($file) && $state;
            }
        }

        return $state;
    }
}
