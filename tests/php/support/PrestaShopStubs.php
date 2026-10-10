<?php

namespace Tests\Support;

require_once __DIR__ . '/global_stubs.php';

class PrestaShopStubs
{
    public static function registerGlobals()
    {
    }

    public static function resetAll()
    {
        \Configuration::reset();
        \Shop::reset();
        \ModuleRegistry::reset();
        \TabRegistry::reset();
        \LanguageRegistry::reset();
        \ToolsState::reset();
        \ProductProperties::reset();
        \ManufacturerRegistry::reset();
        \RequestGlobals::reset();
        \ContextHolder::reset();
        \OrderFixtures::reset();
        \OrderDetailFixtures::reset();
        \CartStub::reset();
        \SmartyStub::reset();
    }
}
