#!/usr/bin/env php
<?php

echo call_user_func(require __DIR__ . '/vendor/autoload.php', function(
    Php\Composer\DI $di,
    ns\c1 $c1,
    ns\c2 $c2,
    ns\c3 $c3,
    ns\c4 $c4,
    ns\c5 $c5
) {
    return \json_encode([
        'invoker-value' => $di->get('bar'),
        'c2-file' => $c2->get('c2'),
        'c31-file' => $c3->get('c31'),
        'c32-file' => $c3->get('c32'),
        'c3-value' => $c3->get('bar'),
        'c4-file' => $c4->get('c4'),
        'c5-file' => $c5->get('c5'),
        'base-dir' => \substr(Php\PACKAGES[Php\VENDOR\PHP_FN\EXTRA_ARRAY]['dir'], -13),
        'vendor-dir' => \substr(Php\PACKAGES[Php\VENDOR\PHP_DI\PHP_DI]['dir'], -34),
    ], JSON_PRETTY_PRINT);
});
