<?php

ini_set('error_reporting', -1);

ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

function dd($arg, ...$args)
{
    foreach (func_get_args() as $argument) {
        print_r($argument);

        echo PHP_EOL;
    }

    die;
}

function vd($arg, ...$args)
{
    foreach (func_get_args() as $argument) {
        var_dump($argument);

        echo PHP_EOL;
    }

    die;
}
