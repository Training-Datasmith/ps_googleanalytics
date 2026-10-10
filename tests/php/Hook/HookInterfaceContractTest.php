<?php

use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookInterface;

class HookInterfaceContractTest extends \PHPUnit\Framework\TestCase
{
    private const EXPECTED_TOTAL = 11;
    private const CLASSES = [
            'HookDisplayHeader',
            'HookDisplayBeforeBodyClosingTag',
            'HookDisplayBackOfficeHeader',
            'HookDisplayFooterProduct',
            'HookDisplayOrderConfirmation',
            'HookActionCarrierProcess',
            'HookActionProductCancel',
            'HookActionObjectProductInCartDeleteBefore',
            'HookActionOrderStatusPostUpdate',
            'HookActionValidateOrder',
            'HookActionCartUpdateQuantityBefore'
    ];

    public function testHookClassesImplementContract()
    {
        $this->assertSame(self::EXPECTED_TOTAL, count(self::CLASSES));
        foreach (self::CLASSES as $class) {
            $fqcn = 'PrestaShop\\Module\\Ps_Googleanalytics\\Hooks\\' . $class;
            $ref = new ReflectionClass($fqcn);
            $this->assertTrue($ref->implementsInterface(HookInterface::class));
            $ctor = $ref->getConstructor();
            $params = $ctor->getParameters();
            $this->assertSame('module', $params[0]->getName());
            $this->assertSame('context', $params[1]->getName());
            $this->assertTrue($ref->hasMethod('run'));
        }
    }
}
