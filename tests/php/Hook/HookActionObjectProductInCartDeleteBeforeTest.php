<?php

use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionObjectProductInCartDeleteBefore;

class HookActionObjectProductInCartDeleteBeforeTest extends ModuleTestCase
{
    public function testRemoveFromCartPersistsItemIdAndQuantity()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G');
        ProductProperties::set(15, ['name' => 'Combo', 'price_amount' => 4]);
        $h = new HookActionObjectProductInCartDeleteBefore($this->createModule(), Context::getContext());
        $h->setParams(['id_product' => 15, 'id_product_attribute' => 7]);
        $h->run();
        $data = $this->createModule()->getDataHandler()->readData();
        $event = $this->decodeRenderedEvent($data[0], 'remove_from_cart');
        $this->assertSame('15-7', (string) $event['items'][0]['item_id']);
        $this->assertSame(1, $event['items'][0]['quantity']);
    }
}
