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
     * dummy test
     */
    public function testDummy()
    {
        //$this->markTestSkipped('Skipped Test.');
        //$this->markTestIncomplete("This test has not been implemented yet.");
    
        //$this->assertInstanceOf('File_Therion', $testSubject);
        //$this->assertTrue($false);
        //$this->assertEquals($expected, $actual, 'Failed!');
        //$this->assertNotEquals($expected, $actual, 'Failed!');
        //$this->assertThat(1, $this->greaterThanOrEqual(2));

    }
    

    /**
     * Test instantiation
     */
    public function testBasicInstantiation()
    {
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
            $c = $sample->getBearing();  // get possibly converted value
            $bc = $sample->getBackBearing();
            
            $sampleB = new File_Therion_Shot();
            $sampleB->setBearing($bc);
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
            
            $c=($c==400)? 0 : $c;       // expect 360 to be adjusted to 0
            $bco=($bco==400)? 0 : $bco; // expect 360 to be adjusted to 0
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
        $this->assertEquals('grads', $sample->getUnit('clino'));
        
        $sample->setUnit('clino', 'grad');
        $this->assertEquals('grads', $sample->getUnit('clino'));
    }
    
    /**
     * Test units calculations
     * 
     * @todo just raw tests implemented, more needed
     */
    public function testUnitsCalculations()
    {
        $this->assertEquals(
            0, File_Therion_Shot::convertValue(0, 'degrees', 'grads') );
        $this->assertEquals(
            400, File_Therion_Shot::convertValue(360, 'degrees', 'grads') );
        $this->assertEquals(
            0, File_Therion_Shot::convertValue(0, 'grads', 'degrees') );
        $this->assertEquals(
            360, File_Therion_Shot::convertValue(400, 'grads', 'degrees') );

        // todo: more tests!
    }
}
?>
