<?php

use PrestaShop\Module\Ps_Googleanalytics\Form\ConfigurationForm;

class ConfigurationFormTreatTest extends ModuleTestCase
{
    public function testTreatPersistsConfiguration()
    {
        ToolsState::setValue('GA_ACCOUNT_ID', 'G-ABC');
        ToolsState::setValue('GA_USERID_ENABLED', '0');
        ToolsState::setValue('GA_ANONYMIZE_ENABLED', '1');
        ToolsState::setValue('GA_TRACK_BACKOFFICE_ENABLED', '0');
        ToolsState::setValue('GA_BACKLOAD_ENABLED', '1');
        ToolsState::setValue('GA_BACKLOAD_DAYS', '12');
        ToolsState::setValue('GA_CANCELLED_STATES', ['6', '7']);
        ToolsState::setSubmit('submitps_googleanalytics');
        $form = new ConfigurationForm($this->createModule());
        $out = $form->treat();
        $this->assertSame('G-ABC', Configuration::get('GA_ACCOUNT_ID'));
        $this->assertTrue((bool) Configuration::get('GANALYTICS_CONFIGURATION_OK'));
        $this->assertFalse((bool) Configuration::get('GA_USERID_ENABLED'));
        $this->assertSame(12, (int) Configuration::get('GA_BACKLOAD_DAYS'));
        $this->assertNotSame('', $out);
    }

}
