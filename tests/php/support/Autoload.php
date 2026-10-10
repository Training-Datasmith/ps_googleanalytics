<?php

namespace Tests\Support;

class Autoload
{
    public static function register()
    {
        $root = dirname(__DIR__, 3);
        $classmap = [
            $root . '/ps_googleanalytics.php',
            $root . '/controllers',
            $root . '/classes',
        ];

        spl_autoload_register(function ($class) use ($classmap) {
            static $map = null;
            if ($map === null) {
                $map = [];
                foreach ($classmap as $path) {
                    if (is_file($path)) {
                        $className = self::classFromFile($path);
                        if ($className !== null) {
                            $map[$className] = $path;
                        }
                        continue;
                    }
                    if (is_dir($path)) {
                        $iterator = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
                        );
                        foreach ($iterator as $file) {
                            if ($file->isFile() && $file->getExtension() === 'php') {
                                $className = self::classFromFile($file->getPathname());
                                if ($className !== null) {
                                    $map[$className] = $file->getPathname();
                                }
                            }
                        }
                    }
                }
            }
            if (isset($map[$class])) {
                require_once $map[$class];
            }
        });
    }

    private static function classFromFile($path)
    {
        $contents = file_get_contents($path);
        if (!preg_match('/namespace\s+([^;]+);/', $contents, $ns)) {
            $namespace = '';
        } else {
            $namespace = trim($ns[1]) . '\\';
        }
        if (preg_match('/\binterface\s+(\w+)/', $contents, $cls)) {
            return $namespace . $cls[1];
        }
        if (preg_match('/\bclass\s+(\w+)/', $contents, $cls)) {
            return $namespace . $cls[1];
        }

        return null;
    }
}
