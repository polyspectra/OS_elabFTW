<?php
use Elabftw\Elabftw\Tools as Tools;

class ToolsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    public function testKdate()
    {
        $this->assertEquals('19690721', Tools::kdate('19690721'));
        $this->assertEquals(date('Ymd'), Tools::kdate('3902348923'));
        $this->assertEquals(date('Ymd'), Tools::kdate('Sun is shining'));
    }

    public function testCheckTitle()
    {
        $this->assertEquals('My super title', Tools::checkTitle('My super title'));
        $this->assertEquals('Yep ', Tools::checkTitle("Yep\n"));
        $this->assertEquals('Untitled', Tools::checkTitle(''));
    }

    public function testCheckBody()
    {
        $this->assertEquals('my body', Tools::checkBody('my body'));
        $this->assertEquals('my body', Tools::checkBody('my body<script></script>'));
    }

    public function testFormatBytes()
    {
        $this->assertEquals('5.08 MiB', Tools::formatBytes(5323423));
        $this->assertEquals('21.4 TiB', Tools::formatBytes(23534909234464));
    }

    public function testFormatDate()
    {
        $this->assertEquals('1969.07.21', Tools::formatDate('19690721'));
        $this->assertEquals('1969-07-21', Tools::formatDate('19690721', '-'));
        $this->assertFalse(Tools::formatDate('196907211'));
    }

    public function testGetExt()
    {
        $this->assertEquals('gif', Tools::getExt('myfile.gif'));
        $this->assertEquals('gif', Tools::getExt('/path/to/myfile.gif'));
        $this->assertEquals('unknown', Tools::getExt('/path/to/myfilegif'));
    }

    public function testBuildStringFromArray()
    {
        $array = array(1, 2, 42);
        $this->assertEquals('1+2+42', Tools::buildStringFromArray($array));
        $this->assertEquals('1-2-42', Tools::buildStringFromArray($array, '-'));
        $this->assertFalse(Tools::buildStringFromArray('pwet'));
    }

    public function testCheckId()
    {
        $this->assertFalse(Tools::checkId('yep'));
        $this->assertFalse(Tools::checkId(-42));
        $this->assertFalse(Tools::checkId(0));
        $this->assertFalse(Tools::checkId(3.1415926535));
        $this->assertEquals(42, Tools::checkId(42));
    }
}
