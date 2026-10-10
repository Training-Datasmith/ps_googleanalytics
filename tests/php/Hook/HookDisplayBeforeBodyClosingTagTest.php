<?php

use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayBeforeBodyClosingTag;

class HookDisplayBeforeBodyClosingTagTest extends ModuleTestCase
{
    public function testSearchEvent()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G');
        Context::getContext()->controller->php_self = 'search';
        $_GET['s'] = 'red shoes';
        $h = new HookDisplayBeforeBodyClosingTag($this->createModule(), Context::getContext());
        $h->run();
        $this->assertAssignedJsContains('search');
        $this->assertAssignedJsContains('red shoes');
    }

    public function testFlushesStoredEvents()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G');
        $m = $this->createModule();
        $m->getDataHandler()->persistData('gtag("event", "add_to_cart", {"send_to": "G"});');
        $h = new HookDisplayBeforeBodyClosingTag($m, Context::getContext());
        $h->run();
        $this->assertAssignedJsContains('add_to_cart');
        $this->assertSame([], $m->getDataHandler()->readData());
    }
}
