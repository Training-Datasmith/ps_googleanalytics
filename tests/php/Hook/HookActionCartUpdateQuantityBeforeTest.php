<?php

use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionCartUpdateQuantityBefore;

class HookActionCartUpdateQuantityBeforeTest extends ModuleTestCase
{
    public function testPersistsAddToCartWithItemIdAndQuantity()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G');
        ProductProperties::set(15, ['name' => 'Combo', 'price_amount' => 10]);
        $h = new HookActionCartUpdateQuantityBefore($this->createModule(), Context::getContext());
        $h->setParams([
            'product' => ['id' => 15],
            'id_product_attribute' => 7,
            'operator' => 'up',
            'quantity' => 3,
        ]);
        $h->run();
        $data = $this->createModule()->getDataHandler()->readData();
        $event = $this->decodeRenderedEvent($data[0], 'add_to_cart');
        $this->assertSame('15-7', (string) $event['items'][0]['item_id']);
        $this->assertSame(3, $event['items'][0]['quantity']);
    }
}
