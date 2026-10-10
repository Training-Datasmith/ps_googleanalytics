<?php

use PrestaShop\Module\Ps_Googleanalytics\Repository\GanalyticsRepository;

class GanalyticsRepositoryTest extends ModuleTestCase
{
    public function testAddOrderAndSentFlags()
    {
        $repo = new GanalyticsRepository();
        $this->assertTrue($repo->addOrder(10, 2));
        $this->assertSame('10', (string) $repo->findGaOrderByOrderId(10));
        $this->assertFalse($repo->hasOrderBeenAlreadySent(10));
        $this->assertTrue($repo->markOrderAsSent(10));
        $this->assertTrue($repo->hasOrderBeenAlreadySent(10));
    }

    public function testFindAllByShopIdAndDateAddFilters()
    {
        $prefix = _DB_PREFIX_;
        $pdo = Tests\Support\Database::pdo();
        $pdo->exec("INSERT INTO `{$prefix}ganalytics` (id_order,id_customer,id_shop,sent,refund_sent,date_add) VALUES (1,1,2,0,NULL,NOW()-INTERVAL 31 MINUTE)");
        $pdo->exec("INSERT INTO `{$prefix}ganalytics` (id_order,id_customer,id_shop,sent,refund_sent,date_add) VALUES (2,1,2,1,NULL,NOW()-INTERVAL 31 MINUTE)");
        $pdo->exec("INSERT INTO `{$prefix}ganalytics` (id_order,id_customer,id_shop,sent,refund_sent,date_add) VALUES (3,1,3,0,NULL,NOW()-INTERVAL 31 MINUTE)");
        $repo = new GanalyticsRepository();
        $rows = $repo->findAllByShopIdAndDateAdd(2);
        $ids = array_column($rows, 'id_order');
        $this->assertContains('1', array_map('strval', $ids));
        $this->assertNotContains('2', array_map('strval', $ids));
        $this->assertNotContains('3', array_map('strval', $ids));
    }
}
