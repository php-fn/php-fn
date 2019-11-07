#!/usr/bin/env php
<?php

(require 'vendor/autoload.php') instanceof Php\Composer\DIClassLoader || Php::fail(__LINE__);

Php\Composer\DIClassLoader::instance()->getContainer() instanceof  Php\Composer\DI || Php::fail(__LINE__);

call_user_func(require 'vendor/autoload.php', static function(Php\Composer\DI $composer, Php\DI\Container $di) {
    $composer === $di || Php::fail(__LINE__);
});

echo call_user_func(require 'vendor/autoload.php', static function(Psr\Container\ContainerInterface $container) {
    return get_class($container);
});
