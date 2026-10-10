<?php

use PrestaShop\Module\Ps_Googleanalytics\Wrapper\OrderWrapper;

class OrderWrapperTest extends ModuleTestCase
{
    public function testWrapOrderComputesTaxAndAffiliation()
    {
        Shop::setFeatureActive(false);
        $order = new Order();
        $order->id = 9;
        $order->id_currency = 4;
        $order->total_paid = '120.00';
        $order->total_shipping = '8.50';
        $order->total_paid_tax_incl = '120.00';
        $order->total_paid_tax_excl = '100.00';
        $order->id_customer = 6;
        $order->payment = 'Bank wire';
        $wrapper = new OrderWrapper(Context::getContext());
        $data = $wrapper->wrapOrder($order);
        $this->assertSame(9, $data['transaction_id']);
        $this->assertSame(20.0, $data['tax']);
        $this->assertSame('EUR', $data['currency']);
        $this->assertSame('Test Shop', $data['affiliation']);
    }
}
