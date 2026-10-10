<?php
use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionCartUpdateQuantityBefore;
class HookActionCartUpdateQuantityBeforeTest extends ModuleTestCase {
  public function testPersistsAddToCart(){ Configuration::updateValue('GA_ACCOUNT_ID','G'); ProductProperties::set(1,['name'=>'P','price_amount'=>10]); $h=new HookActionCartUpdateQuantityBefore($this->createModule(),Context::getContext()); $h->setParams(['product'=>['id'=>1],'id_product_attribute'=>0,'operator'=>'up','quantity'=>3]); $h->run(); $data=$this->createModule()->getDataHandler()->readData(); $this->assertContains('add_to_cart', $data[0]); }
}
