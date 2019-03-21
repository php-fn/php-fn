<?php
/**
 * Copyright (C) php-fn. See LICENSE file for license details.
 */

foreach ([
    '\fn\_\toArray' => 'functions-helper.php',
    '\fn\fail'      => 'functions-fail.php',
    '\fn\sub'       => 'functions.php',
    '\fn\traverse'  => 'functions-map.php',
] as $fnc => $file) {
    if (!function_exists($fnc)) {
        require_once __DIR__ . "/fn/$file" ;
    }
}
