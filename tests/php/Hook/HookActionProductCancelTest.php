<?php

use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionProductCancel;

class HookActionProductCancelTest extends ModuleTestCase
{
    public function testRefundItemIdLiteralFifteenSeven()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G-1');
        OrderDetailFixtures::set(5, [
            'product_id' => 15,
            'product_attribute_id' => 7,
            'product_name' => 'Combo',
        ]);
        $hook = new HookActionProductCancel($this->createModule(), Context::getContext());
        $hook->setParams([
            'id_order_detail' => 5,
            'cancel_quantity' => 2,
            'order' => (object) ['id' => 9],
        ]);
        $hook->run();
        $js = Context::getContext()->cookie->ga_admin_refund;
        $this->assertContains('"refund"', $js);
        $this->assertContains('"item_id":"15-7"', $js);
    }
}
