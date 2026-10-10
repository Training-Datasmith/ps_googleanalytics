<?php

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\Ps_Googleanalytics\Database\Install;
use Tests\Support\Database;
use Tests\Support\PrestaShopStubs;

abstract class ModuleTestCase extends TestCase
{
    protected function setUp()
    {
        PrestaShopStubs::resetAll();
        Database::resetModuleTables();
        $install = new Install($this->createModule());
        $install->installTables();
        $this->seedCatalogSchema();
    }

    protected function seedCatalogSchema()
    {
        $p = _DB_PREFIX_;
        $pdo = Database::pdo();
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$p}category` (`id_category` INT PRIMARY KEY, `level_depth` INT)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$p}category_lang` (`id_category` INT, `id_lang` INT, `name` VARCHAR(64))");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$p}category_product` (`id_category` INT, `id_product` INT)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$p}category_shop` (`id_category` INT, `id_shop` INT)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$p}product_attribute_combination` (`id_product_attribute` INT, `id_attribute` INT)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$p}attribute` (`id_attribute` INT PRIMARY KEY, `id_attribute_group` INT, `position` INT)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$p}attribute_group` (`id_attribute_group` INT PRIMARY KEY, `position` INT)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$p}attribute_lang` (`id_attribute` INT, `id_lang` INT, `name` VARCHAR(64))");
    }

    protected function assertAssignedJsContains($needle)
    {
        $this->assertArrayHasKey('jsCode', SmartyStub::$vars);
        $this->assertContains($needle, SmartyStub::$vars['jsCode']);
    }

    protected function tearDown()
    {
        Database::truncateModuleTables();
        PrestaShopStubs::resetAll();
    }

    protected function createModule()
    {
        $module = new Ps_Googleanalytics();
        $module->context = Context::getContext();

        return $module;
    }

    protected function extractEventJson($js, $eventName)
    {
        $pattern = '/gtag\("event", "' . preg_quote($eventName, '/') . '", (\{.*?\})\);/s';
        if (!preg_match($pattern, $js, $m)) {
            return null;
        }
        $json = $m[1];
        $json = preg_replace('/"event_callback": function\(\) \{.*?\}/s', '"event_callback": null', $json);
        $json = preg_replace('/,\s*"event_callback": null/', '', $json);

        return json_decode($json, true);
    }
}
