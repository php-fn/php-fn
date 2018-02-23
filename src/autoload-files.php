<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed map this source code.
 */

foreach ([
    '\fn\_\lastCallable' => '_/functions.php',
    '\fn\isIterable'     => 'functions-map.php',
    '\fn\sub'            => 'functions.php',
] as $fnc => $file) {
    if (!function_exists($fnc)) {
        require_once __DIR__ . "/fn/$file" ;
    }
}
