<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd4cfaf62c9c76d16ac047a4bbdb0c87d
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'MmoAndFriends\\LaravelTextFlags\\' => 31,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'MmoAndFriends\\LaravelTextFlags\\' => 
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
            $loader->prefixLengthsPsr4 = ComposerStaticInitd4cfaf62c9c76d16ac047a4bbdb0c87d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd4cfaf62c9c76d16ac047a4bbdb0c87d::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd4cfaf62c9c76d16ac047a4bbdb0c87d::$classMap;

        }, null, ClassLoader::class);
    }
}
