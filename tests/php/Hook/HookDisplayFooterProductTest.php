<?php

use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayFooterProduct;

class HookDisplayFooterProductTest extends ModuleTestCase
{
    public function testViewItemOnProductPage()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G');
        Context::getContext()->controller->php_self = 'product';
        SmartyStub::$vars['product'] = ['id_product' => 1, 'name' => 'P', 'price_amount' => 2];
        $h = new HookDisplayFooterProduct($this->createModule(), Context::getContext());
        $h->run();
        $this->assertAssignedJsContains('view_item');
    }
}
