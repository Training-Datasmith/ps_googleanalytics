<?php
class PsGoogleanalyticsTest extends ModuleTestCase {
  public function testMetadataAndConfigXmlAgree(){ $m=$this->createModule(); $xml=simplexml_load_file(MODULE_ROOT.'/config.xml'); $this->assertSame((string)$xml->name,$m->name); $this->assertSame((string)$xml->version,$m->version); $this->assertRegExp('/^\d+\.\d+\.\d+$/', $m->version); }
  public function testGetToolsIsSingleton(){ $m=$this->createModule(); $this->assertSame($m->getTools(),$m->getTools()); }
}
