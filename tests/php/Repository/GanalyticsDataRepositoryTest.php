<?php

use PrestaShop\Module\Ps_Googleanalytics\Repository\GanalyticsDataRepository;

class GanalyticsDataRepositoryTest extends ModuleTestCase
{
    public function testInsertFindDelete()
    {
        $repo = new GanalyticsDataRepository();
        $this->assertTrue($repo->addNewRow(1, 1, json_encode(['a'])));
        $this->assertSame(json_encode(['a']), $repo->findDataByCartIdAndShopId(1, 1));
        $this->assertTrue($repo->addNewRow(1, 1, json_encode(['b'])));
        $this->assertSame(json_encode(['b']), $repo->findDataByCartIdAndShopId(1, 1));
        $this->assertTrue($repo->deleteRow(1, 1));
        $this->assertFalse($repo->findDataByCartIdAndShopId(1, 1));
    }
}
