<?php

use PrestaShop\Module\Ps_Googleanalytics\Repository\CarrierRepository;

class CarrierRepositoryTest extends ModuleTestCase
{
    public function testFindByCarrierId()
    {
        $prefix = _DB_PREFIX_;
        Tests\Support\Database::pdo()->exec("CREATE TABLE IF NOT EXISTS `{$prefix}carrier` (`id_carrier` INT NOT NULL, `name` VARCHAR(64) NOT NULL)");
        Tests\Support\Database::pdo()->exec("INSERT INTO `{$prefix}carrier` (id_carrier,name) VALUES (5,'Postal')");
        $repo = new CarrierRepository();
        $this->assertSame('Postal', $repo->findByCarrierId(5));
        $this->assertFalse($repo->findByCarrierId(99));
    }
}
