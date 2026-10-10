<?php

use PrestaShop\Module\Ps_Googleanalytics\Database\Install;
use PrestaShop\Module\Ps_Googleanalytics\Database\Uninstall;

class InstallUninstallTest extends ModuleTestCase
{
    public function testInstallAndUninstallTables()
    {
        $module = $this->createModule();
        $install = new Install($module);
        $this->assertTrue($install->setDefaultConfiguration());
        $states = json_decode(Configuration::get('GA_CANCELLED_STATES'), true);
        $this->assertContains('6', array_map('strval', $states));
        $this->assertTrue($install->registerHooks());
        $this->assertSame(12, count(ModuleRegistry::getHooks($module->name)));
        $this->assertTrue($install->installTab());
        $tab = TabRegistry::getByClass('AdminGanalyticsAjax');
        $this->assertNotNull($tab);
        $this->assertSame(-1, (int) $tab->id_parent);
        $uninstall = new Uninstall();
        $this->assertTrue($uninstall->uninstallTables());
        $pdo = Tests\Support\Database::pdo();
        $this->assertFalse($pdo->query('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'ganalytics"')->fetch());
    }
}
