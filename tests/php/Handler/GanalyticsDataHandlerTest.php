<?php

use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsDataHandler;

class GanalyticsDataHandlerTest extends ModuleTestCase
{
    public function testReadPersistDeleteCycle()
    {
        $handler = new GanalyticsDataHandler(9, 1);
        $this->assertSame([], $handler->readData());
        $this->assertTrue($handler->persistData('evt-a'));
        $data = $handler->readData();
        $this->assertCount(1, $data);
        $this->assertTrue($handler->persistData('evt-b'));
        $data = $handler->readData();
        $this->assertCount(2, $data);
        $this->assertContains('evt-a', $data);
        $this->assertContains('evt-b', $data);
        $this->assertTrue($handler->deleteData());
        $this->assertSame([], $handler->readData());
    }

    public function testInvalidJsonReturnsEmptyArray()
    {
        $repo = new PrestaShop\Module\Ps_Googleanalytics\Repository\GanalyticsDataRepository();
        $repo->addNewRow(3, 1, 'not-json');
        $handler = new GanalyticsDataHandler(3, 1);
        $this->assertSame([], $handler->readData());
    }

    public function testShopIsolation()
    {
        $repo = new PrestaShop\Module\Ps_Googleanalytics\Repository\GanalyticsDataRepository();
        $repo->addNewRow(4, 2, json_encode(['x']));
        $handler = new GanalyticsDataHandler(4, 1);
        $this->assertSame([], $handler->readData());
    }
}
