<?php
use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionObjectProductInCartDeleteBefore;
class HookActionObjectProductInCartDeleteBeforeTest extends ModuleTestCase {
  public function testRemoveFromCartPersisted(){ Configuration::updateValue('GA_ACCOUNT_ID','G'); ProductProperties::set(2,['name'=>'P','price_amount'=>4]); $h=new HookActionObjectProductInCartDeleteBefore($this->createModule(),Context::getContext()); $h->setParams(['id_product'=>2,'id_product_attribute'=>0]); $h->run(); $this->assertContains('remove_from_cart', $this->createModule()->getDataHandler()->readData()[0]); }
}
