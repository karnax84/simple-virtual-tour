<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit126749344dd8297418449c484119edb5
{
    public static $files = array (
        '0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Polyfill\\Mbstring\\' => 26,
        ),
        'P' => 
        array (
            'PhpMyAdmin\\SqlParser\\' => 21,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Polyfill\\Mbstring\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-mbstring',
        ),
        'PhpMyAdmin\\SqlParser\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmyadmin/sql-parser/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit126749344dd8297418449c484119edb5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit126749344dd8297418449c484119edb5::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit126749344dd8297418449c484119edb5::$classMap;

        }, null, ClassLoader::class);
    }
}
