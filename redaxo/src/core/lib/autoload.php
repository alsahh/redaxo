<?php

/**
 * REDAXO Autoloader.
 *
 * This class was originally copied from the Symfony Framework:
 * Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * Adjusted in very many places
 *
 * @package redaxo\core
 */
class rex_autoload
{
    /**
     * @var Composer\Autoload\ClassLoader
     */
    protected static $composerLoader;

    protected static $registered = false;
    protected static $cacheFile = null;
    protected static $cacheChanged = false;
    protected static $reloaded = false;
    protected static $dirs = [];
    protected static $addedDirs = [];
    protected static $classes = [];

    /**
     * Register rex_autoload in spl autoloader.
     */
    public static function register()
    {
        if (self::$registered) {
            return;
        }

        ini_set('unserialize_callback_func', 'spl_autoload_call');

        if (!self::$composerLoader) {
            self::$composerLoader = require rex_path::core('vendor/autoload.php');
            // Unregister Composer Autoloader because we call self::$composerLoader->loadClass() manually
            self::$composerLoader->unregister();
        }

        if (false === spl_autoload_register([__CLASS__, 'autoload'])) {
            throw new Exception(sprintf('Unable to register %s::autoload as an autoloading method.', __CLASS__));
        }

        self::$cacheFile = rex_path::cache('autoload.cache');
        self::loadCache();
        register_shutdown_function([__CLASS__, 'saveCache']);

        self::$registered = true;
    }

    /**
     * Unregister rex_autoload from spl autoloader.
     */
    public static function unregister()
    {
        spl_autoload_unregister([__CLASS__, 'autoload']);
        self::$registered = false;
    }

    /**
     * Handles autoloading of classes.
     *
     * @param string $class A class name.
     *
     * @return bool Returns true if the class has been loaded
     */
    public static function autoload($class)
    {
        // class already exists
        if (self::classExists($class)) {
            return true;
        }

        $force = false;
        $lowerClass = strtolower($class);
        if (isset(self::$classes[$lowerClass])) {
            // we have a class path for the class, let's include it
            if (is_readable(self::$classes[$lowerClass])) {
                require_once self::$classes[$lowerClass];
                if (self::classExists($class)) {
                    return true;
                }
            }
            // there is a class path in cache, but the file does not exist or does not contain the class any more
            // but maybe the class exists in another already known file now
            // so all files have to be analysed again => $force reload
            $force = true;
            unset(self::$classes[$lowerClass]);
            self::$cacheChanged = true;
        }

        // Return true if class exists after calling $composerLoader
        if (self::$composerLoader->loadClass($class) && self::classExists($class)) {
            return true;
        }

        // Class not found, so reanalyse all directories if not already done or if $force==true
        // but only if an admin is logged in
        if ((!self::$reloaded || $force) && ($user = rex_backend_login::createUser()) && $user->isAdmin()) {
            self::reload($force);
            return self::autoload($class);
        }

        return false;
    }

    /**
     * Returns whether the given class/interface/trait exists.
     *
     * @param string $class
     *
     * @return bool
     */
    private static function classExists($class)
    {
        return class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false);
    }

    /**
     * Loads the cache.
     */
    private static function loadCache()
    {
        if (!self::$cacheFile || !is_readable(self::$cacheFile)) {
            return;
        }

        list(self::$classes, self::$dirs) = json_decode(file_get_contents(self::$cacheFile), true);
    }

    /**
     * Saves the cache.
     */
    public static function saveCache()
    {
        if (!self::$cacheChanged) {
            return;
        }
        if (!is_writable(dirname(self::$cacheFile))) {
            throw new Exception("Unable to write autoload cachefile '" . self::$cacheFile . "'!");
        }

        // remove obsolete dirs from cache
        foreach (self::$dirs as $dir => $files) {
            if (!in_array($dir, self::$addedDirs)) {
                unset(self::$dirs[$dir]);
            }
        }

        rex_file::putCache(self::$cacheFile, [self::$classes, self::$dirs]);
        self::$cacheChanged = false;
    }

    /**
     * Reanalyses all added directories.
     *
     * @param bool $force If true, all files are reanalysed, otherwise only new and changed files
     */
    public static function reload($force = false)
    {
        if ($force) {
            self::$classes = [];
            self::$dirs = [];
        }
        foreach (self::$addedDirs as $dir) {
            self::_addDirectory($dir);
        }
        self::$reloaded = true;
    }

    /**
     * Removes the cache.
     */
    public static function removeCache()
    {
        rex_file::delete(self::$cacheFile);
    }

    /**
     * Adds a directory to the autoloading system if not yet present.
     *
     * @param string $dir The directory to look for classes
     */
    public static function addDirectory($dir)
    {
        $dir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
        if (in_array($dir, self::$addedDirs)) {
            return;
        }
        self::$addedDirs[] = $dir;
        if (!isset(self::$dirs[$dir])) {
            self::_addDirectory($dir);
            self::$cacheChanged = true;
        }
    }

    /**
     * Returns the classes.
     *
     * @return string[]
     */
    public static function getClasses()
    {
        return array_keys(self::$classes);
    }

    /**
     * @param string $dir
     */
    private static function _addDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        if (!isset(self::$dirs[$dir])) {
            self::$dirs[$dir] = [];
        }
        $files = self::$dirs[$dir];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $path => $file) {
            /** @var SplFileInfo $file */
            if (!$file->isFile() || !in_array($file->getExtension(), ['php', 'inc'])) {
                continue;
            }

            unset($files[$path]);
            $checksum = md5_file($path);
            if (isset(self::$dirs[$dir][$path]) && self::$dirs[$dir][$path] === $checksum) {
                continue;
            }
            self::$dirs[$dir][$path] = $checksum;
            self::$cacheChanged = true;

            $classes = self::findClasses($path);
            foreach ($classes as $class) {
                $class = strtolower($class);
                if (!isset(self::$classes[$class])) {
                    self::$classes[$class] = $path;
                }
            }
        }
        foreach ($files as $path) {
            unset(self::$dirs[$path]);
            self::$cacheChanged = true;
        }
    }

    /**
     * Extract the classes in the given file.
     *
     * The method is copied from Composer (with little changes):
     * https://github.com/composer/composer/blob/a2a70380c14a20b3f611d849eae7342f2e35c763/src/Composer/Autoload/ClassMapGenerator.php#L89-L146
     *
     * @param string $path The file to check
     *
     * @throws \RuntimeException
     *
     * @return array The found classes
     */
    private static function findClasses($path)
    {
        try {
            $contents = php_strip_whitespace($path);
        } catch (\Exception $e) {
            throw new \RuntimeException('Could not scan for classes inside ' . $path . ": \n" . $e->getMessage(), 0, $e);
        }

        // return early if there is no chance of matching anything in this file
        if (!preg_match('{\b(?:class|interface|trait)\s}i', $contents)) {
            return [];
        }

        // strip heredocs/nowdocs
        $contents = preg_replace('{<<<\'?(\w+)\'?(?:\r\n|\n|\r)(?:.*?)(?:\r\n|\n|\r)\\1(?=\r\n|\n|\r|;)}s', 'null', $contents);
        // strip strings
        $contents = preg_replace('{"[^"\\\\]*(\\\\.[^"\\\\]*)*"|\'[^\'\\\\]*(\\\\.[^\'\\\\]*)*\'}s', 'null', $contents);
        // strip leading non-php code if needed
        if (substr($contents, 0, 2) !== '<?') {
            $contents = preg_replace('{^.+?<\?}s', '<?', $contents);
        }
        // strip non-php blocks in the file
        $contents = preg_replace('{\?>.+<\?}s', '?><?', $contents);
        // strip trailing non-php code if needed
        $pos = strrpos($contents, '?>');
        if (false !== $pos && false === strpos(substr($contents, $pos), '<?')) {
            $contents = substr($contents, 0, $pos);
        }

        preg_match_all('{
            (?:
                 \b(?<![\$:>])(?P<type>class|interface|trait) \s+ (?P<name>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)
               | \b(?<![\$:>])(?P<ns>namespace) (?P<nsname>\s+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(?:\s*\\\\\s*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*)? \s*[\{;]
            )
        }ix', $contents, $matches);

        $classes = [];
        $namespace = '';

        for ($i = 0, $len = count($matches['type']); $i < $len; ++$i) {
            if (!empty($matches['ns'][$i])) {
                $namespace = str_replace([' ', "\t", "\r", "\n"], '', $matches['nsname'][$i]) . '\\';
            } else {
                $classes[] = ltrim($namespace . $matches['name'][$i], '\\');
            }
        }

        return $classes;
    }
}
