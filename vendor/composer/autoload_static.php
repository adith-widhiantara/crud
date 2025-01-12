<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit251edaf83262493aa71318fb5007d632
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'Adithwidhiantara\\Crud\\' => 22,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Adithwidhiantara\\Crud\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit251edaf83262493aa71318fb5007d632::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit251edaf83262493aa71318fb5007d632::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit251edaf83262493aa71318fb5007d632::$classMap;

        }, null, ClassLoader::class);
    }
}
