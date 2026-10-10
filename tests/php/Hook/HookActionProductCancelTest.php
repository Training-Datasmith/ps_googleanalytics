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
        $event = $this->decodeRenderedEvent($js, 'refund');
        $this->assertSame(9, $event['transaction_id']);
        $this->assertSame('15-7', (string) $event['items'][0]['item_id']);
        $this->assertSame(2, $event['items'][0]['quantity']);
    }
}
