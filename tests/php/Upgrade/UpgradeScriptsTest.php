<?php
class UpgradeScriptsTest extends ModuleTestCase {
  public function testUpgrade310CreatesTable(){ $pdo=Tests\Support\Database::pdo(); $pdo->exec('DROP TABLE IF EXISTS '._DB_PREFIX_.'ganalytics_data'); require MODULE_ROOT.'/upgrade/install-3.1.0.php'; $this->assertTrue(upgrade_module_3_1_0($this->createModule())); $this->assertNotFalse($pdo->query('SHOW TABLES LIKE "'._DB_PREFIX_.'ganalytics_data"')->fetch()); }
}
