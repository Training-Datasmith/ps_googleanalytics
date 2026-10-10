<?php

use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionOrderStatusPostUpdate;

class HookActionOrderStatusPostUpdateTest extends ModuleTestCase
{
    public function testRefundOnCancelledState()
    {
        Configuration::updateValue('GA_ACCOUNT_ID', 'G-1');
        Configuration::updateValue('GA_CANCELLED_STATES', json_encode(['6']));
        $prefix = _DB_PREFIX_;
        Tests\Support\Database::pdo()->exec("INSERT INTO `{$prefix}ganalytics` (id_order,id_customer,id_shop,sent,refund_sent,date_add) VALUES (55,1,1,0,NULL,NOW())");
        $hook = new HookActionOrderStatusPostUpdate($this->createModule(), Context::getContext());
        $hook->setParams(['id_order' => 55, 'newOrderStatus' => (object) ['id' => '6']]);
        $hook->run();
        $this->assertContains('refund', Context::getContext()->cookie->ga_admin_refund);
        $sent = Tests\Support\Database::pdo()->query('SELECT refund_sent FROM ' . $prefix . 'ganalytics WHERE id_order=55')->fetchColumn();
        $this->assertSame('1', (string) $sent);
    }
}
