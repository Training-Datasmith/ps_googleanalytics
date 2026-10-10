<?php
use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionCarrierProcess;
class HookActionCarrierProcessTest extends ModuleTestCase {
  public function testShippingInfoPersisted(){ Configuration::updateValue('GA_ACCOUNT_ID','G'); $p=_DB_PREFIX_; Tests\Support\Database::pdo()->exec("CREATE TABLE IF NOT EXISTS `{$p}carrier` (id_carrier INT,name VARCHAR(64))"); Tests\Support\Database::pdo()->exec("INSERT INTO `{$p}carrier` VALUES (3,'Postal')"); $cart=new Cart(); $cart->id_carrier=3; $h=new HookActionCarrierProcess($this->createModule(),Context::getContext()); $h->setParams(['cart'=>$cart]); $h->run(); $this->assertContains('add_shipping_info', $this->createModule()->getDataHandler()->readData()[0]); }
}
