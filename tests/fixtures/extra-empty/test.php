#!/usr/bin/env php
<?php

(require 'vendor/autoload.php') instanceof php\Composer\DIClassLoader || php\fail(__LINE__);

php\Composer\DIClassLoader::instance()->getContainer() instanceof  php\Composer\DI || php\fail(__LINE__);

call_user_func(require 'vendor/autoload.php', static function(php\Composer\DI $composer, php\DI\Container $di) {
    $composer === $di || php\fail(__LINE__);
});

echo call_user_func(require 'vendor/autoload.php', static function(Psr\Container\ContainerInterface $container) {
    return get_class($container);
});
