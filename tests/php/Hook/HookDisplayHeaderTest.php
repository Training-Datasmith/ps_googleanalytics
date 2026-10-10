<?php
use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayHeader;
class HookDisplayHeaderTest extends ModuleTestCase {
  public function testReturnsEmptyWithoutAccount(){ $h=new HookDisplayHeader($this->createModule(),Context::getContext()); $this->assertSame('', $h->run()); }
  public function testAssignsEscapedAccount(){ Configuration::updateValue('GA_ACCOUNT_ID','G-1<'); $h=new HookDisplayHeader($this->createModule(),Context::getContext()); $h->run(); $this->assertSame('G-1&lt;', SmartyStub::$vars['gaAccountId']); }
}
