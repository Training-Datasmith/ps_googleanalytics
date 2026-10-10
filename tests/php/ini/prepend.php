<?php

error_reporting(-1);
if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
    $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
}
if (!isset($_SERVER['REQUEST_TIME'])) {
    $_SERVER['REQUEST_TIME'] = (int) $_SERVER['REQUEST_TIME_FLOAT'];
}
if (!isset($_SERVER['SCRIPT_NAME'])) {
    $_SERVER['SCRIPT_NAME'] = 'phpunit';
}
