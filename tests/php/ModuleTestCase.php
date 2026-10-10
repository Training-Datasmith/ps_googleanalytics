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

    protected function decodeJsonBlacklistObject($encoded)
    {
        $decoded = json_decode($encoded, true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), json_last_error_msg());

        return $decoded;
    }

    protected function extractEventJson($js, $eventName)
    {
        $pattern = '/gtag\s*\(\s*["\']event["\']\s*,\s*["\']' . preg_quote($eventName, '/') . '["\']\s*,\s*/';
        if (!preg_match($pattern, $js, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $start = $match[0][1] + strlen($match[0][0]);
        if (!isset($js[$start]) || $js[$start] !== '{') {
            return null;
        }
        $objectLiteral = $this->extractBalancedBraces($js, $start);
        if ($objectLiteral === null) {
            return null;
        }
        $json = $this->removeEventCallbackFromObjectLiteral($objectLiteral);

        return json_decode($json, true);
    }

    private function extractBalancedBraces($js, $start)
    {
        $depth = 0;
        $len = strlen($js);
        for ($i = $start; $i < $len; $i++) {
            if ($js[$i] === '{') {
                $depth++;
            }
            if ($js[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($js, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    private function removeEventCallbackFromObjectLiteral($objectLiteral)
    {
        $needle = '"event_callback"';
        $pos = strpos($objectLiteral, $needle);
        if ($pos === false) {
            return $objectLiteral;
        }
        $before = substr($objectLiteral, 0, $pos);
        $i = $pos + strlen($needle);
        $len = strlen($objectLiteral);
        while ($i < $len && ctype_space($objectLiteral[$i])) {
            $i++;
        }
        if ($i >= $len || $objectLiteral[$i] !== ':') {
            return $objectLiteral;
        }
        $i++;
        while ($i < $len && ctype_space($objectLiteral[$i])) {
            $i++;
        }
        $valueEnd = $this->skipJsonishValue($objectLiteral, $i);
        $after = substr($objectLiteral, $valueEnd);
        $before = rtrim($before, " \n\r\t,");
        $after = ltrim($after, " \n\r\t,");
        if ($after === '' || $after[0] === '}') {
            return $before . $after;
        }

        return $before . ',' . $after;
    }

    private function skipJsonishValue($s, $i)
    {
        $len = strlen($s);
        if ($i >= $len) {
            return $i;
        }
        if (substr($s, $i, 8) === 'function') {
            $i += 8;
            while ($i < $len && $s[$i] !== '{') {
                $i++;
            }
            if ($i < $len && $s[$i] === '{') {
                $end = $this->extractBalancedBraces($s, $i);
                if ($end !== null) {
                    return $i + strlen($end);
                }
            }

            return $i;
        }
        if ($s[$i] === '{') {
            $end = $this->extractBalancedBraces($s, $i);
            if ($end !== null) {
                return $i + strlen($end);
            }

            return $i + 1;
        }
        if ($s[$i] === '"') {
            $i++;
            while ($i < $len) {
                if ($s[$i] === '\\') {
                    $i += 2;
                    continue;
                }
                if ($s[$i] === '"') {
                    return $i + 1;
                }
                $i++;
            }

            return $len;
        }

        while ($i < $len && strpos(",} \n\r\t", $s[$i]) === false) {
            $i++;
        }

        return $i;
    }

    protected function decodeRenderedEvent($js, $eventName)
    {
        $data = $this->extractEventJson($js, $eventName);
        $this->assertInternalType('array', $data, 'Expected gtag event: ' . $eventName);

        return $data;
    }
}
