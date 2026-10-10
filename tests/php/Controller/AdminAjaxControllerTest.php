<?php
class AdminAjaxControllerTest extends ModuleTestCase {
  public function testRequiresEmployee(){ require_once MODULE_ROOT.'/controllers/admin/AdminGanalyticsAjax.php'; OrderFixtures::set(3,['id_customer'=>1]); ToolsState::setValue('orderid',3); $c=new AdminGanalyticsAjaxController(); unset($c->context->employee); $c->context->employee=new stdClass(); try{$c->init();}catch(AjaxExitException $e){} $this->assertSame('KO',$c->ajaxRendered); $c->context->employee=(object)['id'=>1]; try{$c->init();}catch(AjaxExitException $e){} $this->assertSame('OK',$c->ajaxRendered); }
}
