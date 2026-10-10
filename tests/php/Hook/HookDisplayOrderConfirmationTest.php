<?php

use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayOrderConfirmation;
use PrestaShop\Module\Ps_Googleanalytics\Repository\GanalyticsRepository;

class HookDisplayOrderConfirmationTest extends ModuleTestCase
{
    public function testPurchaseRenderedForNewOrder()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G');
        OrderFixtures::set(7, ['id_shop' => 1, 'id_cart' => 99, 'id_customer' => 1]);
        CartStub::setProductsForCart(99, [
            [
                'id_product' => 15,
                'id_product_attribute' => 7,
                'name' => 'Combo',
                'price_amount' => 10,
                'quantity' => 2,
            ],
        ]);
        $o = new Order(7);
        $h = new HookDisplayOrderConfirmation($this->createModule(), Context::getContext());
        $h->setParams(['order' => $o]);
        $h->run();
        $this->assertArrayHasKey('jsCode', SmartyStub::$vars);
        $purchase = $this->decodeRenderedEvent(SmartyStub::$vars['jsCode'], 'purchase');
        $this->assertSame(7, $purchase['transaction_id']);
        $this->assertSame('15-7', (string) $purchase['items'][0]['item_id']);
        $this->assertSame(2, $purchase['items'][0]['quantity']);
        $payment = $this->decodeRenderedEvent(SmartyStub::$vars['jsCode'], 'add_payment_info');
        $this->assertSame('15-7', (string) $payment['items'][0]['item_id']);
    }

    public function testSkipsPurchaseWhenOrderAlreadySent()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G');
        OrderFixtures::set(7, ['id_shop' => 1, 'id_cart' => 99, 'id_customer' => 1]);
        CartStub::setProductsForCart(99, [
            [
                'id_product' => 15,
                'id_product_attribute' => 7,
                'name' => 'Combo',
                'price_amount' => 10,
                'quantity' => 1,
            ],
        ]);
        $repo = new GanalyticsRepository();
        $repo->addOrder(7, 1);
        $repo->markOrderAsSent(7);
        $h = new HookDisplayOrderConfirmation($this->createModule(), Context::getContext());
        $h->setParams(['order' => new Order(7)]);
        $out = $h->run();
        $this->assertSame('', $out);
        $this->assertFalse(isset(SmartyStub::$vars['jsCode']));
    }
}
