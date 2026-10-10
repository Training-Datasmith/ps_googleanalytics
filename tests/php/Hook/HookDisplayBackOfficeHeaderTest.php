<?php
use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayBackOfficeHeader;
class HookDisplayBackOfficeHeaderTest extends ModuleTestCase {
  public function testAddsCssOnConfigurePage(){ ToolsState::setValue('configure','ps_googleanalytics'); $h=new HookDisplayBackOfficeHeader($this->createModule(),Context::getContext()); $h->run(); $this->assertNotEmpty(Context::getContext()->controller->addCssCalls); }
}
