<?php

use PrestaShop\Module\Ps_Googleanalytics\GoogleAnalyticsTools;

class GoogleAnalyticsToolsTest extends ModuleTestCase
{
    public function testJsonEncodeWithBlacklist()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G-TEST');
        $tools = new GoogleAnalyticsTools();
        $this->assertSame('{}', $tools->jsonEncodeWithBlacklist([]));
        $out = $tools->jsonEncodeWithBlacklist(['name' => 'Café']);
        $this->assertContains('"name": "Café"', $out);
        $raw = $tools->jsonEncodeWithBlacklist(['event_callback' => 'function(){return 1;}', 'value' => 1], ['event_callback']);
        $this->assertContains('function(){return 1;}', $raw);
        $this->assertContains('"value": 1', $raw);
    }

    public function testRenderEventInjectsSendTo()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G-TEST');
        $tools = new GoogleAnalyticsTools();
        $js = $tools->renderEvent('view_item', ['currency' => 'EUR', 'value' => 10.5]);
        $this->assertContains('gtag("event", "view_item"', $js);
        $this->assertContains('"send_to": "G-TEST"', $js);
        $this->assertContains('"currency": "EUR"', $js);
    }

    public function testRenderPurchaseEventRejectsNonArrayProducts()
    {
        $tools = new GoogleAnalyticsTools();
        $this->assertNull($tools->renderPurchaseEvent('x', ['transaction_id' => 1, 'customer' => 1, 'affiliation' => 'a', 'value' => 1, 'tax' => 0, 'shipping' => 0, 'currency' => 'EUR'], 'https://shop.test/cb'));
    }

    public function testRenderPurchaseEventBuildsCallbackPayload()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G-TEST');
        $tools = new GoogleAnalyticsTools();
        $js = $tools->renderPurchaseEvent(
            [['item_id' => 5]],
            ['transaction_id' => 42, 'customer' => 7, 'affiliation' => 'Shop', 'value' => 19.9, 'tax' => 1.5, 'shipping' => 3, 'currency' => 'EUR'],
            'https://shop.test/module/ps_googleanalytics/ajax'
        );
        $this->assertContains('gtag("event", "purchase"', $js);
        $this->assertContains('42', $js);
        $this->assertContains('7', $js);
        $this->assertContains('function()', $js);
    }
}
