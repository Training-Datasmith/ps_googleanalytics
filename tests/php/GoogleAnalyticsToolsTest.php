<?php

use PrestaShop\Module\Ps_Googleanalytics\GoogleAnalyticsTools;

class GoogleAnalyticsToolsTest extends ModuleTestCase
{
    public function testJsonEncodeWithBlacklist()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G-TEST');
        $tools = new GoogleAnalyticsTools();
        $this->assertSame([], $this->decodeJsonBlacklistObject($tools->jsonEncodeWithBlacklist([])));
        $decoded = $this->decodeJsonBlacklistObject($tools->jsonEncodeWithBlacklist(['name' => 'Café']));
        $this->assertSame('Café', $decoded['name']);
        $blacklisted = $tools->jsonEncodeWithBlacklist(['event_callback' => '{"raw":true}', 'value' => 1], ['event_callback']);
        $withCallback = $this->decodeJsonBlacklistObject($blacklisted);
        $this->assertSame(['raw' => true], $withCallback['event_callback']);
        $valueOnly = $this->decodeJsonBlacklistObject($tools->jsonEncodeWithBlacklist(['value' => 1]));
        $this->assertSame(1, $valueOnly['value']);
    }

    public function testRenderEventInjectsSendTo()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G-TEST');
        $tools = new GoogleAnalyticsTools();
        $js = $tools->renderEvent('view_item', ['currency' => 'EUR', 'value' => 10.5]);
        $event = $this->decodeRenderedEvent($js, 'view_item');
        $this->assertSame('G-TEST', $event['send_to']);
        $this->assertSame('EUR', $event['currency']);
        $this->assertEquals(10.5, $event['value']);
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
        $purchase = $this->decodeRenderedEvent($js, 'purchase');
        $this->assertSame(42, $purchase['transaction_id']);
        $this->assertSame('EUR', $purchase['currency']);
        $this->assertContains('function()', $js);
        $this->assertSame(1, preg_match('/\$\.get\([^,]+,\s*(\{.*?\})\)/s', $js, $matches));
        $callback = $this->decodeJsonBlacklistObject($matches[1]);
        $this->assertSame(42, $callback['orderid']);
        $this->assertSame(7, $callback['customer']);
    }
}
