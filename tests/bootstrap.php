<?php

// ALL Errors.  ALL OF THEM.
error_reporting(-1);

$composer = __DIR__.'/../vendor/autoload.php';
if (is_file($composer)) {
    require_once $composer;
}
