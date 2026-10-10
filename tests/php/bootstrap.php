<?php

error_reporting(-1);
ini_set('display_errors', '1');
if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
    $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
}
if (!isset($_SERVER['REQUEST_TIME'])) {
    $_SERVER['REQUEST_TIME'] = time();
}
if (!isset($_SERVER['SCRIPT_NAME'])) {
    $_SERVER['SCRIPT_NAME'] = '/opt/phpunit/phpunit-7.5.20.phar';
}

define('TESTS_ROOT', __DIR__);
define('MODULE_ROOT', dirname(__DIR__, 2));

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '8.1.0');
}
if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', getenv('DB_PREFIX') ?: 'ps_');
}
if (!defined('_MYSQL_ENGINE_')) {
    define('_MYSQL_ENGINE_', 'InnoDB');
}
if (!defined('_PS_MODULE_DIR_')) {
    define('_PS_MODULE_DIR_', MODULE_ROOT . '/');
}

require_once TESTS_ROOT . '/support/Database.php';
require_once TESTS_ROOT . '/support/PrestaShopStubs.php';
require_once TESTS_ROOT . '/support/Autoload.php';
if (class_exists('PHPUnit\\Framework\\TestCase')) {
    require_once TESTS_ROOT . '/ModuleTestCase.php';
}

Tests\Support\Autoload::register();
Tests\Support\PrestaShopStubs::registerGlobals();
Tests\Support\Database::connectFromEnv();
