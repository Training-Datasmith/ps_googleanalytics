<?php

class FrontAjaxControllerTest extends ModuleTestCase
{
    public function testUnknownOrderReturnsKo()
    {
        require_once MODULE_ROOT . '/controllers/front/ajax.php';
        $controller = new ps_GoogleanalyticsAjaxModuleFrontController();
        ToolsState::setValue('orderid', 99999);
        ToolsState::setValue('customer', 1);
        Context::getContext()->customer->id = 5;
        try {
            $controller->initContent();
            $body = '';
        } catch (AjaxExitException $e) {
            $body = $controller->ajaxRendered;
        }
        $this->assertSame('KO', $body);
    }
}
