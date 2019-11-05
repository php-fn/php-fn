#!/usr/bin/env php
<?php

echo call_user_func(require '/tmp/vendor-di-test-manual/autoload.php', static function(
        Psr\Container\ContainerInterface $c,
        Php\c2 $c2
) {
    return json_encode([
        '$c->get(\'foo\')' => $c->get('foo'),
        '$c->get(\'c3\')' => $c->get('c3'),
        '$c->get(\'c31\')' => $c->get('c31'),
        '$c2->has(\'bar\')' => $c2->has('bar'),
        '$c2->get(\'c3\')' => $c2->get('c3'),
        '$c2->get(\'c31\')' => $c2->get('c31'),
        '$c2->get(Php\c31::class)->get(\'c31\')' => $c2->get(Php\c31::class)->get('c31'),
    ], JSON_PRETTY_PRINT);
}) . PHP_EOL;
