<?php
use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionValidateOrder;
class HookActionValidateOrderTest extends ModuleTestCase {
  public function testStoresAdminOrderIds(){ Context::getContext()->controller->controller_name='AdminOrders'; OrderFixtures::set(8,['id_shop'=>1]); $h=new HookActionValidateOrder($this->createModule(),Context::getContext()); $h->setParams(['order'=>new Order(8)]); $h->run(); $this->assertSame(8, Context::getContext()->cookie->ga_admin_order); }
}
