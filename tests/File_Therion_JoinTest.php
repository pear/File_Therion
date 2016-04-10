<?php
/**
 * Therion use cases test.
 *
 * PHP version 5
 *
 * @category   file
 * @package    File_Therion_Tests
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
 
//includepath is loaded by phpUnit from phpunit.xml
require_once 'tests/File_TherionTestBase.php';

/**
 * PHPUnit test class for File_Therion use cases.
 * 
 * This will test some realistic use cases.
 */
class File_Therion_JoinTest extends File_TherionTestBase {

  
/* ---------- TESTS ---------- */
/* test functions are public and start with "test*".
 
    
    /**
     * Parsing test
     */
    public function testParsing()
    {
        // simple join of two scraps
        $sampleLine = File_Therion_Line::parse('join scrapA scrapB');
        $sample = File_Therion_Join::parse($sampleLine);
        $this->assertInstanceOf('File_Therion_Join', $sample);
        $this->assertEquals("join scrapA scrapB", $sample->toString());
        
        // simple join of two lines
        $sampleLine = File_Therion_Line::parse('join lineA lineB:end');
        $sample = File_Therion_Join::parse($sampleLine);
        $this->assertInstanceOf('File_Therion_Join', $sample);
        $this->assertEquals("join lineA lineB:end", $sample->toString());
        
        $sampleLine = File_Therion_Line::parse('join lineA:0 lineB:end');
        $sample = File_Therion_Join::parse($sampleLine);
        $this->assertInstanceOf('File_Therion_Join', $sample);
        $this->assertEquals("join lineA:0 lineB:end", $sample->toString());
        
        // threesome join
        $sampleLine = File_Therion_Line::parse('join lineA lineB:end lineC:3');
        $sample = File_Therion_Join::parse($sampleLine);
        $this->assertInstanceOf('File_Therion_Join', $sample);
        $this->assertEquals("join lineA lineB:end lineC:3", $sample->toString());
    }
    
    /**
     * Test options
     */
    public function testOptions()
    {
        $sampleLine = File_Therion_Line::parse('join scrapA scrapB');
        $sample = File_Therion_Join::parse($sampleLine);
        $this->assertInstanceOf('File_Therion_Join', $sample);
        $this->assertEquals("", $sample->getOption('smooth'));
        $this->assertEquals(0, $sample->getOption('count'));
        
        $sample->setOption('count', 3);
        $this->assertEquals("", $sample->getOption('smooth'));
        $this->assertEquals(3, $sample->getOption('count'));
        
        $sample->setOption('smooth', "on");
        $this->assertEquals("on", $sample->getOption('smooth'));
        $this->assertEquals(3, $sample->getOption('count'));
    }
    
    /**
     * Test options
     */
    public function testOptionsParsing()
    {
        // no options
        $sampleLine = File_Therion_Line::parse('join scrapA scrapB');
        $sample = File_Therion_Join::parse($sampleLine);
        $this->assertInstanceOf('File_Therion_Join', $sample);
        $this->assertEquals("", $sample->getOption('smooth'));
        $this->assertEquals(0, $sample->getOption('count'));
        $this->assertEquals("join scrapA scrapB", $sample->toString());
        
        // smooth option
        $sampleLine = File_Therion_Line::parse('join scrapA scrapB -smooth on');
        $sample = File_Therion_Join::parse($sampleLine);
        $this->assertInstanceOf('File_Therion_Join', $sample);
        $this->assertEquals("on", $sample->getOption('smooth'));
        $this->assertEquals(0, $sample->getOption('count'));
        $this->assertEquals("join scrapA scrapB -smooth on", $sample->toString());
        
        // count option
        $sampleLine = File_Therion_Line::parse('join scrapA scrapB -count 1');
        $sample = File_Therion_Join::parse($sampleLine);
        $this->assertInstanceOf('File_Therion_Join', $sample);
        $this->assertEquals("", $sample->getOption('smooth'));
        $this->assertEquals(1, $sample->getOption('count'));
        $this->assertEquals("join scrapA scrapB -count 1", $sample->toString());
        
        // smooth and count options
        $sampleLine = File_Therion_Line::parse('join scrapA scrapB -smooth auto -count 2');
        $sample = File_Therion_Join::parse($sampleLine);
        $this->assertInstanceOf('File_Therion_Join', $sample);
        $this->assertEquals("auto", $sample->getOption('smooth'));
        $this->assertEquals(2, $sample->getOption('count'));
        $this->assertEquals("join scrapA scrapB -smooth auto -count 2", $sample->toString());
    }
    
    /**
     * Test joining in object mode
     */
    public function testBasicUsageScraps()
    {
        // joining two scraps
        $scrapA = new File_Therion_Scrap("scrapA");
        $scrapB = new File_Therion_Scrap("scrapB");
        
        // simple mode
        $join = new File_Therion_Join(array($scrapA, $scrapB));
        $this->assertEquals("join scrapA scrapB", $join->toString());
        
        // manual mode
        $join = new File_Therion_Join();
        $join->addArgument($scrapA);
        $join->addArgument($scrapB);
        $this->assertEquals("join scrapA scrapB", $join->toString());
    }
    
    /**
     * Test joining in object mode
     */
    public function testBasicUsageLines()
    {
        // joining two scrap lines
        $lineA = new File_Therion_ScrapLine("wall");
        $lineA->setOption('id', "lineA");
        $lineB = new File_Therion_ScrapLine("wall");
        $lineB->setOption('id', "lineB");
        
        // simple mode
        $join = new File_Therion_Join(array($lineA, $lineB));
        $this->assertEquals("join lineA lineB", $join->toString());
        
        // with mark and threesome
        $lineC = new File_Therion_ScrapLine("wall");
        $lineC->setOption('id', "lineC");
        $lp = $lineC->addPoint();
        $lp->setMark("myMark");
        $join->addArgument($lp);
        $this->assertEquals("join lineA lineB lineC:myMark", $join->toString());
    }
    
    /**
     * Test OO functionality
     */
    public function testOOCapabilitysBasic()
    {
        // joining two scraps
        $scrapA = new File_Therion_Scrap("scrapA");
        $scrapB = new File_Therion_Scrap("scrapB");
        
        $join = new File_Therion_Join(array($scrapA, $scrapB));
        $this->assertEquals("join scrapA scrapB", $join->toString());
        
        // rename one scrap
        $scrapA->setName("newScrapName");
        $this->assertEquals("join newScrapName scrapB", $join->toString());
        
    }
    
    /**
     * Test OO functionality
     */
    public function testOOCapabilitysRenaming()
    {
        // init testcase
        $lineA = new File_Therion_ScrapLine("wall");
        $lineA->setOption('id', "lineA");
        $lineB = new File_Therion_ScrapLine("wall");
        $lineB->setOption('id', "lineB");
        $join = new File_Therion_Join(array($lineA, $lineB));
        $lineC = new File_Therion_ScrapLine("wall");
        $lineC->setOption('id', "lineC");
        $lp = $lineC->addPoint();
        $lp->setMark("myMark");
        $join->addArgument($lp);
        $this->assertEquals("join lineA lineB lineC:myMark", $join->toString());
        
        // OO-test: rename the line from outside
        $lineC->setOption('id', "lCnew");
        $this->assertEquals("join lineA lineB lCnew:myMark", $join->toString());
        
        // OO-test: rename mark from outside
        $lp->setMark("nwMark");
        $this->assertEquals("join lineA lineB lCnew:nwMark", $join->toString());
        
        // OO-test: get first argument and rename it
        $join->getArguments()[0]->setOption('id', "rline");
        $this->assertEquals("join rline lineB lCnew:nwMark", $join->toString());
        
        // Test that getting and altering argumetns doesnt affect internal state
        $args = $join->getArguments();
        unset($args[0]);
        $args[1] = null;
        $this->assertEquals("join rline lineB lCnew:nwMark", $join->toString());
    }
}
?>