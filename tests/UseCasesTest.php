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
class File_TherionUseCaseTest extends File_TherionTestBase {

  
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
     * Parse rabbit cave example
     */
    public function testParseRabbitCave()
    {
        // Parse the test file contents recursively into one big virtual file
        $th = File_Therion::parse($this->testdata_base_therion.'/basics/rabbit.th');
        
        // assert expected contents:
        // only a survey is contained, everything else belongs to it
        $this->assertEquals("ISO8859-2", $th->getEncoding());
        $this->assertEquals(1, count($th->getSurveys()));
        $this->assertEquals(0, count($th->getCentrelines()));
        $this->assertEquals(0, count($th->getMaps()));
        $this->assertEquals(0, count($th->getSurfaces()));
        $this->assertEquals(0, count($th->getScraps()));
        
        
        // assert survey data
        $survey = array_shift($th->getSurveys());
        $this->assertEquals(1, count($survey->getCentrelines()));
        $this->assertEquals(3, count($survey->getJoins()));
        $this->assertEquals(0, count($survey->getEquates()));
        $this->assertEquals(2, count($survey->getMaps()));
        $this->assertEquals(1, count($survey->getSurfaces()));
        $this->assertEquals(4, count($survey->getScraps()));
        
        
        // assert the surveys centreline
        $centreline = array_shift($survey->getCentrelines());
        
        $date = $centreline->getDate();
        $this->assertEquals(1, count($date));
        $this->assertEquals("1997.08.10", $date->toString());
        
        $team = $centreline->getTeam();
        $this->assertEquals(3, count($team));
        $this->assertEquals(
        // TODO: Missing encoding support will scramble Stachos lastname
        //    array('"Martin Budaj"', '"Stacho Mudrák"','"Miroslav Hofer"'),
        //    array($team[0]->toString(), $team[1]->toString(), $team[2]->toString())
            array('"Martin Budaj"', '"Miroslav Hofer"'),
            array($team[0]->toString(), $team[2]->toString())
        );
        $this->assertEquals(null, $centreline->getExploDate());
        $this->assertEquals(array(), $centreline->getExploTeam());
        
        $shots = $centreline->getShots();
        $this->assertEquals(15, count($shots));
        foreach ($shots as $s) {
            $this->assertEquals("normal", $s->getStyle());
            $this->assertEquals("grads", $s->getUnit("compass"));
            $this->assertEquals("grads", $s->getUnit("clino"));
            $this->assertEquals(
                array("from", "to", "compass", "clino", "tape"),
                $s->getOrder() // values as-given
            );
            $this->assertEquals(
                array("from", "to", "bearing", "gradient", "length"),
                $s->getOrder(true) // normalized values
            );
        }
        $this->assertEquals( // test shot idx=8
            array(
                8, 9, 382, 8, 7.28,
                array(false, false, false, false)
            ),
            array(
                $shots[8]->getFrom()->getName(),
                $shots[8]->getTo()->getName(),
                $shots[8]->getBearing(),
                $shots[8]->getGradient(),
                $shots[8]->getLength(),
                array(
                    $shots[8]->getFlag('surface'),
                    $shots[8]->getFlag('splay'),
                    $shots[8]->getFlag('duplicate'),
                    $shots[8]->getFlag('approximate'),
                )
            )
        );
        $this->assertEquals( // test shot idx=13 
            array(
                13, 14, 295, 3, 11.9,
                array(true, false, false, false)
            ),
            array(
                $shots[13]->getFrom()->getName(),
                $shots[13]->getTo()->getName(),
                $shots[13]->getBearing(),
                $shots[13]->getGradient(),
                $shots[13]->getLength(),
                array(
                    $shots[13]->getFlag('surface'),
                    $shots[13]->getFlag('splay'),
                    $shots[13]->getFlag('duplicate'),
                    $shots[13]->getFlag('approximate'),
                )
            )
        );
        
        $stations = $centreline->getStations();
        $this->assertEquals(1, count($stations));
        $station_15 = $centreline->getStations("15");
        $this->assertEquals(true, $station_15->isFixed());
        $this->assertEquals(
            array(
                'coords' => array(20, 40, 646.23),
                'std'    => array(0, 0, 0)
            ),
            $station_15->getFix()
        );
        
        // extend ignore 5
        // extend ignore 12
        $extends = $centreline->getExtends();
        $this->assertEquals(2, count($extends));
        $this->assertEquals('ignore', $extends[0]['spec']);
        $this->assertInstanceOf('File_Therion_Station', $extends[0]['obj']);
        $this->assertEquals('5', $extends[0]['obj']->getName());
        
        $this->assertEquals('ignore', $extends[1]['spec']);
        $this->assertInstanceOf('File_Therion_Station', $extends[1]['obj']);
        $this->assertEquals('12', $extends[1]['obj']->getName());
        
        
        // assert joins of survey
        $this->assertEquals(
            array(
                array("ew1:0",   "ew2:end"),
                array("ew1:end", "ew2:0"),
                array("ps1",     "ps2")
            ),
            $survey->getJoins()
        );
        
        
        // assert maps of survey
        $maps = $survey->getMaps();
        $this->assertEquals(2, count($maps));
        $this->assertEquals("pdx", $maps[0]->getName());
        $this->assertEquals(
            "Rabbit Cave -- extended elevation",
            $maps[0]->getOption('title')
        );
        $this->assertEquals(
            array("xs1", "xs2"), $maps[0]->getElements()
        );
        $this->assertEquals("pdp", $maps[1]->getName());
        $this->assertEquals(
            "Rabbit Cave - plan",
            $maps[1]->getOption('title')
        );
        $this->assertEquals(
            array("ps1", "ps2"), $maps[1]->getElements()
        );
    
    
        // assert surface
        $surfaces = $survey->getSurfaces();
        // TODO: surface checks not implemented!
        //       missing underlying code...
        
    }

}
?>
