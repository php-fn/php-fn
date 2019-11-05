#!/usr/bin/env php
<?php

echo call_user_func(require '/tmp/vendor-di-test-manual/autoload.php', static function(
    Psr\Container\ContainerInterface $c,
//    \php\c1 $c1,
    php\c2 $c2
) {
    return json_encode([
        '$c->get(\'foo\')' => $c->get('foo'),
//        '$c->get(\'bar\')' => $c->get('bar'),
        '$c->get(\'c3\')' => $c->get('c3'),
        '$c->get(\'c31\')' => $c->get('c31'),
//        '$c1->get(\'bar\')' => $c1->get('bar'),
        '$c2->has(\'bar\')' => $c2->has('bar'),
        '$c2->get(\'c3\')' => $c2->get('c3'),
        '$c2->get(\'c31\')' => $c2->get('c31'),
        '$c2->get(php\c31::class)->get(\'c31\')' => $c2->get(php\c31::class)->get('c31'),
    ], JSON_PRETTY_PRINT);
}) . PHP_EOL;
