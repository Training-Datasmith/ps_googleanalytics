<?php

use PrestaShop\Module\Ps_Googleanalytics\Wrapper\ProductWrapper;

class ProductWrapperTest extends ModuleTestCase
{
    public function testPrepareItemKeepsNumericZeroPriceAmount()
    {
        $wrapper = new ProductWrapper(Context::getContext());
        $item = $wrapper->prepareItemFromProduct(['id_product' => 1, 'name' => 'Free', 'price_amount' => 0, 'price' => '€0.00']);
        $this->assertSame(0, $item['price']);
        $itemFloat = $wrapper->prepareItemFromProduct(['id_product' => 10, 'name' => 'Free', 'price_amount' => 0.0, 'price' => '€0.00']);
        $this->assertSame(0.0, $itemFloat['price']);
    }

    public function testPrepareItemFallsBackWhenPriceAmountMissingOrEmpty()
    {
        $wrapper = new ProductWrapper(Context::getContext());
        $item = $wrapper->prepareItemFromProduct(['id_product' => 2, 'name' => 'P', 'price' => 12.5]);
        $this->assertSame(12.5, $item['price']);
        $item2 = $wrapper->prepareItemFromProduct(['id_product' => 3, 'name' => 'P', 'price_amount' => '', 'price' => 3.3]);
        $this->assertSame(3.3, $item2['price']);
        $item3 = $wrapper->prepareItemFromProduct(['id_product' => 4, 'name' => 'P', 'price_amount' => null, 'price' => 4.4]);
        $this->assertSame(4.4, $item3['price']);
        $item4 = $wrapper->prepareItemFromProduct(['id_product' => 5, 'name' => 'P', 'price_amount' => false, 'price' => 5.5]);
        $this->assertSame(5.5, $item4['price']);
    }

    public function testStringZeroPriceAmountIsPreserved()
    {
        $wrapper = new ProductWrapper(Context::getContext());
        $item = $wrapper->prepareItemFromProduct(['id_product' => 6, 'name' => 'P', 'price_amount' => '0', 'price' => 9.9]);
        $this->assertSame('0', $item['price']);
    }

    public function testPurchaseStyleItemIdLiteralFifteenSeven()
    {
        $wrapper = new ProductWrapper(Context::getContext());
        $item = $wrapper->prepareItemFromProduct(['id_product' => 15, 'name' => 'Combo', 'price_amount' => 1, 'id_product_attribute' => 7]);
        $this->assertSame('15-7', $item['item_id']);
    }

    public function testPrepareItemListAssignsIndexes()
    {
        $wrapper = new ProductWrapper(Context::getContext());
        $items = $wrapper->prepareItemListFromProductList([
            ['id_product' => 1, 'name' => 'A', 'price_amount' => 1],
            ['id_product' => 2, 'name' => 'B', 'price_amount' => 2],
        ]);
        $this->assertSame([0, 1], array_column($items, 'index'));
    }
}
