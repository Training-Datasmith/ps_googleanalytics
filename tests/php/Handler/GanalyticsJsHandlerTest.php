<?php

use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsJsHandler;

class GanalyticsJsHandlerTest extends ModuleTestCase
{
    public function testGenerateReturnsNullWhenAccountMissing()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', '');
        $module = $this->createModule();
        $handler = new GanalyticsJsHandler($module, Context::getContext());
        $this->assertNull($handler->generate('TOKEN'));
        $this->assertArrayNotHasKey('jsCode', SmartyStub::$vars);
    }

    public function testGenerateAssignsJsCode()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G-1');
        $module = $this->createModule();
        $handler = new GanalyticsJsHandler($module, Context::getContext());
        $out = $handler->generate('TOKEN');
        $this->assertSame('TOKEN', SmartyStub::$vars['jsCode']);
        $this->assertSame('ga_tag.tpl', SmartyStub::$lastDisplayTemplate);
        $this->assertContains('ga_tag.tpl', $out);
    }
}
