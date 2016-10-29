<?php
/**
 * Therion shot unit test cases
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
 * PHPUnit test class for testing various datatypes.
 */
class File_Therion_ShotTest extends File_TherionTestBase {


/* ---------- TESTS ---------- */
/* test functions are public and start with "test*".

    /**
     * Test instantiation
     */
    public function testBasicInstantiation()
    {
        $sample = new File_Therion_Shot();
        $this->assertEquals(null, $sample->getFrom());
        $this->assertEquals(null, $sample->getTo());
        $this->assertEquals(0, $sample->getLength());
        $this->assertEquals(0, $sample->getBearing());
        $this->assertEquals(0, $sample->getGradient());
        
        $sample = new File_Therion_Shot("0", "-");
        $this->assertInstanceOf('File_Therion_Station', $sample->getFrom());
        $this->assertEquals("0", $sample->getFrom()->getName());
        $this->assertTrue($sample->getFlag('splay'));
        $this->assertInstanceOf('File_Therion_Station', $sample->getTo());
        $this->assertEquals("-", $sample->getTo()->getName());
        $this->assertEquals(0, $sample->getLength());
        $this->assertEquals(0, $sample->getBearing());
        $this->assertEquals(0, $sample->getGradient());
        
        $sample = new File_Therion_Shot("0", "1.1", 1, 2.2, 3.33);
        $this->assertInstanceOf('File_Therion_Station', $sample->getFrom());
        $this->assertEquals("0", $sample->getFrom()->getName());
        $this->assertFalse($sample->getFlag('splay'));
        $this->assertInstanceOf('File_Therion_Station', $sample->getTo());
        $this->assertEquals("1.1", $sample->getTo()->getName());
        $this->assertEquals(1, $sample->getLength());
        $this->assertEquals(2.2, $sample->getBearing());
        $this->assertEquals(3.33, $sample->getGradient());
    }
    
    /**
     * Test shot flags
     */
    public function testFlags()
    {
        $sample = new File_Therion_Shot();
        $expected = array(
            'surface'     => false,
            'splay'       => false,
            'duplicate'   => false,
            'approximate' => false,
            'approx'      => false,  // test alias!
        );
        foreach ($expected as $f=>$v) {
            $this->assertEquals($v, $sample->getFlag($f));
        }
        
        $f = 'surface'; $expected[$f] = true;
        $sample->setFlag($f, true);
        foreach ($expected as $f=>$v) {
            $this->assertEquals($v, $sample->getFlag($f));
        }
        
        $f = 'splay'; $expected[$f] = true;
        $sample->setFlag($f, true);
        foreach ($expected as $f=>$v) {
            $this->assertEquals($v, $sample->getFlag($f));
        }
        
        $f = 'duplicate'; $expected[$f] = true;
        $sample->setFlag($f, true);
        foreach ($expected as $f=>$v) {
            $this->assertEquals($v, $sample->getFlag($f));
        }
        
        $f = 'approx';
        $expected['approx'] = true; $expected['approximate'] = true;
        $sample->setFlag($f, true);
        foreach ($expected as $f=>$v) {
            $this->assertEquals($v, $sample->getFlag($f));
        }
        $f = 'approximate';
        foreach ($expected as $f=>$v) {
            $this->assertEquals($v, $sample->getFlag($f));
        }
        
        // test for splay flag implicitely by station name
        // also test for renaming to "." with and without active flag
        foreach (array('.', '-') as $splayChar) {
            $sample = new File_Therion_Shot();
            $this->assertFalse($sample->getFlag('splay'));
            $sample->setFrom("1");
            $sample->setTo("2");
            $this->assertFalse($sample->getFlag('splay'));
            $sample->setFrom($splayChar);
            $this->assertTrue($sample->getFlag('splay'));
            $sample->setTo($splayChar);
            $this->assertTrue($sample->getFlag('splay'));
            $sample->setFrom("1");
            $this->assertTrue($sample->getFlag('splay'));
            $sample->setTo("2");
            $this->assertFalse($sample->getFlag('splay'));
            $sample->setTo($splayChar);
            $this->assertTrue($sample->getFlag('splay'));
            $sample->setFlag('splay', false);
            $this->assertTrue($sample->getFlag('splay'));
            $sample->setFlag('splay', true);
            $this->assertTrue($sample->getFlag('splay'));
        
            // test for splay flag set explicitely
            $sample = new File_Therion_Shot();
            $sample->setFrom("1");
            $sample->setTo("2");
            $this->assertFalse($sample->getFlag('splay'));
            $sample->setFlag('splay', false);
            $this->assertFalse($sample->getFlag('splay'));
            $sample->setFlag('splay', true);
            $this->assertTrue($sample->getFlag('splay'));
            $sample->setFlag('splay', false);
            $this->assertFalse($sample->getFlag('splay'));
            
            // test for splay flag explicitely and then renamed station
            $sample->setFlag('splay', true);
            $this->assertTrue($sample->getFlag('splay'));
            $sample->setTo($splayChar);
            $this->assertTrue($sample->getFlag('splay'));
            $sample->setTo("2");
            $this->assertTrue($sample->getFlag('splay'));
            $sample->setFlag('splay', false);
            $this->assertFalse($sample->getFlag('splay'));
        }
        
        
        // TODO: test wrong invocatios
        
    }
    
    
    /**
     * Test parsing
     */
    public function testParsing()
    {
        // a simple basic survey example
        $order  = array('from', 'to', 'length', 'bearing', 'gradient');
        $data   = array('1.1', '1.2', 10, 234, -10);
        $sample = File_Therion_Shot::parse($data, $order);
        $this->assertInstanceOf('File_Therion_Shot', $sample);
        $this->assertEquals(
            array(
                '1.1',
                '1.2',
                10,
                234,
                -10
            ),
            array(
                $sample->getFrom()->getName(),
                $sample->getTo()->getName(),
                $sample->getLength(),
                $sample->getBearing(),
                $sample->getGradient(),
            )
        );
        
        // test splay detection
        $data   = array('1.1', '.', 10, 234, -10);
        $sample = File_Therion_Shot::parse($data, $order);
        $this->assertTrue($sample->getFlag('splay'));
        $data   = array('1.1', '-', 10, 234, -10);
        $sample = File_Therion_Shot::parse($data, $order);
        $this->assertTrue($sample->getFlag('splay'));
        $data   = array('.', '1.1', 10, 234, -10);
        $sample = File_Therion_Shot::parse($data, $order);
        $this->assertTrue($sample->getFlag('splay'));
        $data   = array('-', '1.1', 10, 234, -10);
        $sample = File_Therion_Shot::parse($data, $order);
        $this->assertTrue($sample->getFlag('splay'));
        $data   = array('1.0', '1.1', 10, 234, -10);
        $sample = File_Therion_Shot::parse($data, $order);
        $this->assertFalse($sample->getFlag('splay'));
    }
    
    
    /**
     * test backbearing utility function
     */
    public function testBackBearing()
    {
        // Test degrees
        foreach (range(0, 360) as $co) {
            $sample = new File_Therion_Shot();
            $sample->setBearing($co);
            $sample->setUnit('bearing', 'degrees');
            $c = $sample->getBearing();  // get possibly converted value
            $bc = $sample->getBackBearing();
            
            $sampleB = new File_Therion_Shot();
            $sampleB->setBearing($bc);
            $sampleB->setUnit('bearing', 'degrees');
            $bco = $sampleB->getBackBearing();
            
            $c=($c==360)? 0 : $c;       // expect 360 to be adjusted to 0
            $bco=($bco==360)? 0 : $bco; // expect 360 to be adjusted to 0
            $this->assertEquals($c, $bco, "deg=$c (co=$co); bc=$bc; bco=$bco");
        }
        
        // Test grads
        foreach (range(0, 400) as $co) {
            $sample = new File_Therion_Shot();
            $sample->setUnit('bearing', 'grads');
            $sample->setBearing($co);
            $c = $sample->getBearing();  // get possibly converted value
            $bc = $sample->getBackBearing();
            
            $sampleB = new File_Therion_Shot();
            $sampleB->setUnit('bearing', 'grads');
            $sampleB->setBearing($bc);
            $bco = $sampleB->getBackBearing();
            
            $c=($c==400)? 0 : $c;       // expect 400 to be adjusted to 0
            $bco=($bco==400)? 0 : $bco; // expect 400 to be adjusted to 0
            $this->assertEquals($c, $bco, "grad=$c (co=$co); bc=$bc; bco=$bco");
        }
    }
    
    /**
     * test backgradient utility function
     */
    public function testBackGradient()
    {
        foreach (range(-90, 90) as $c) {
            $sample = new File_Therion_Shot();
            $sample->setGradient($c);
            $bc = $sample->getBackGradient();
            
            $sampleB = new File_Therion_Shot();
            $sampleB->setGradient($bc);
            $bco = $sampleB->getBackGradient();
            
            $this->assertEquals($c, $bco, "deg=$c; bc=$bc; bco=$bco");
        }
    }
    

    /**
     * test aliasing / unaliasing of fields
     */
    public function testAliases()
    {
        // test aliasing
        $this->assertEquals(
            'tape', File_Therion_Shot::aliasField('length'));
        $this->assertEquals(
            'ceiling', File_Therion_Shot::aliasField('up'));

        // test unaliasing
        $this->assertEquals(
            'length', File_Therion_Shot::unaliasField('tape'));
        $this->assertEquals(
            'up', File_Therion_Shot::unaliasField('ceiling'));
        
        // keep aliases or normalized value when already resolved/aliased
        $this->assertEquals(
            'length', File_Therion_Shot::unaliasField('length'));
        $this->assertEquals(
            'tape', File_Therion_Shot::aliasField('tape'));
        
        // keep arbitary unknown values
        $this->assertEquals(
            'fooxyz', File_Therion_Shot::aliasField('fooxyz'));
        $this->assertEquals(
            'fooxyz', File_Therion_Shot::unaliasField('fooxyz')); 
    }
    
    /**
     * test get order
     */
    public function testGetOrder()
    {
        $sample = new File_Therion_Shot();
        $sample->setOrder(array('from', 'to', 'tape', 'ceiling'));
        $this->assertEquals(
            array('from', 'to', 'tape', 'ceiling'),
            $sample->getOrder()  // aliases untouched
        );
        $this->assertEquals(
            array('from', 'to', 'length', 'up'),
            $sample->getOrder(true) // normalized
        );
    }
    
    /**
     * Test setting/getting units
     */
    public function testSetGetUnits()
    {
        $sample = new File_Therion_Shot();
        $sample->setUnit('clino', 'grads');
        $this->assertEquals('grads', $sample->getUnit('clino')->getType());
        
        $sample->setUnit('clino', 'grad');
        $this->assertEquals('grad', $sample->getUnit('clino')->getType());
    }
    
    /**
     * Test getting data based on order
     */
    public function testGetOrderedData()
    {
        $order  = array('from', 'to', 'length', 'bearing', 'gradient');
        $data   = array('1.1', '1.2', 10, 234, -10);
        $sample = File_Therion_Shot::parse($data, $order);
        $from   = $sample->getFrom();
        $to     = $sample->getTo();
        
        // swap order
        $sample->setOrder(array_reverse($order));
        
        $this->assertEquals(
            array(-10, 234, 10, $to, $from),
            $sample->getOrderedData()
        );
    }
    
}
?>