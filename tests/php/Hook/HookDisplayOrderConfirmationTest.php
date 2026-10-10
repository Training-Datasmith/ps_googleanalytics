<?php

use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayOrderConfirmation;

class HookDisplayOrderConfirmationTest extends ModuleTestCase
{
    public function testPurchaseRenderedForNewOrder()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G');
        OrderFixtures::set(7, ['id_shop' => 1, 'id_cart' => 99, 'id_customer' => 1]);
        CartStub::setProductsForCart(99, [
            ['id_product' => 1, 'name' => 'P', 'price_amount' => 10, 'quantity' => 1],
        ]);
        $o = new Order(7);
        $h = new HookDisplayOrderConfirmation($this->createModule(), Context::getContext());
        $h->setParams(['order' => $o]);
        $h->run();
        $this->assertAssignedJsContains('purchase');
        $this->assertAssignedJsContains('add_payment_info');
    }
}
