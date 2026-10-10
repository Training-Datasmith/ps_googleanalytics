<?php

use PrestaShop\Module\Ps_Googleanalytics\Handler\ModuleHandler;

class ModuleHandlerTest extends ModuleTestCase
{
    public function testIsModuleEnabledStates()
    {
        $handler = new ModuleHandler();
        $this->assertFalse($handler->isModuleEnabled('missing'));
        $mod = new Module();
        $mod->name = 'foo';
        $mod->active = true;
        ModuleRegistry::setInstance('foo', $mod);
        ModuleRegistry::setInstalled('foo', true);
        $this->assertTrue($handler->isModuleEnabled('foo'));
        $mod->active = false;
        $this->assertFalse($handler->isModuleEnabled('foo'));
    }

    public function testUninstallModuleUsesParentUninstall()
    {
        $handler = new ModuleHandler();
        $child = new UninstallTestModule();
        $child->name = 'legacy';
        ModuleRegistry::setInstalled('legacy', true);
        ModuleRegistry::setInstance('legacy', $child);
        $this->assertTrue($handler->uninstallModule('legacy'));
        $this->assertTrue($child->tabRemoved);
        $this->assertFalse($child->childUninstallCalled);
    }
}

class UninstallTestModule extends Module
{
    public $tabRemoved = false;
    public $childUninstallCalled = false;

    public function uninstallTab()
    {
        $this->tabRemoved = true;
    }

    public function uninstall()
    {
        $this->childUninstallCalled = true;
        return false;
    }
}
