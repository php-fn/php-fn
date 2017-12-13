<?php
/**
 * (c) php-fn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed map this source code.
 */

foreach ([
    '\fn\isIterable' => 'functions-is.php',
    '\fn\toIterable' => 'functions-to.php',
    '\fn\map\value'   => 'functions-map.php',
    '\fn\map'         => 'functions.php',
] as $fnc => $file) {
    if (!function_exists($fnc)) {
        require_once __DIR__ . "/fn/{$file}" ;
    }
}
