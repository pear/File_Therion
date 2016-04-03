<?php
/**
 * Therion cave survey unit test cases
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
 * PHPUnit test class for File_Therion_Centreline.
 */
class File_Therion_CentrelineTest extends File_TherionTestBase {
    
    

/* ---------- TESTS ---------- */
/* test functions are public and start with "test*". */
    
    
    /**
     * test instantiation
     */
    public function testBasicInstantiation()
    {
        $sample = new File_Therion_Centreline();
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        $this->assertEquals(0, count($sample)); // SPL count shots
        $this->assertEquals("", $sample->getOption("id"));
        
        $sample = new File_Therion_Centreline(array());
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        $this->assertEquals(0, count($sample)); // SPL count shots
        $this->assertEquals("", $sample->getOption("id"));
        
        $sample = new File_Therion_Centreline(array('id' => "fooID"));
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        $this->assertEquals(0, count($sample)); // SPL count shots
        $this->assertEquals("fooID", $sample->getOption("id"));
        
        
        // wrong invocation
        try {
            $sample = new File_Therion_Centreline("foo");
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
        }
    }
    
    /**
     * test team member handling
     */
    public function testTeamMembers()
    {
        $sample = new File_Therion_Centreline();
        $p_foo = new File_Therion_Person("Foo", "Bar");
        $p_baz = new File_Therion_Person("Baz", "Faz");
        $p_cde = new File_Therion_Person("Cde", "Fgh");
        $this->assertEquals(array(), $sample->getTeam());
        
        // test adding team members
        $sample->addTeam($p_foo);
        $this->assertEquals(array($p_foo), $sample->getTeam());
        $this->assertEquals(array(), $sample->getTeamRoles($p_foo));
        
        $sample->addTeam($p_baz, "instruments");
        $this->assertEquals(array($p_foo, $p_baz), $sample->getTeam());
        $this->assertEquals(array(), $sample->getTeamRoles($p_foo));
        $this->assertEquals(array("instruments"), $sample->getTeamRoles($p_baz));
        
        $sample->addTeam($p_cde, array("dog", "clino"));
        $this->assertEquals(array($p_foo, $p_baz, $p_cde), $sample->getTeam());
        $this->assertEquals(array(), $sample->getTeamRoles($p_foo));
        $this->assertEquals(array("instruments"), $sample->getTeamRoles($p_baz));
        $this->assertEquals(array("dog", "clino"), $sample->getTeamRoles($p_cde));
        
        
        // test adding explo team members
        $sample->addExploTeam($p_foo);
        $sample->addExploTeam($p_baz);
        $this->assertEquals(array($p_foo, $p_baz), $sample->getExploTeam());
        // ensure there was no interference:
        $this->assertEquals(array($p_foo, $p_baz, $p_cde), $sample->getTeam());
        $this->assertEquals(array("instruments"), $sample->getTeamRoles($p_baz));
        $this->assertEquals(array("dog", "clino"), $sample->getTeamRoles($p_cde));
        
        $sample->clearTeam();
        $this->assertEquals(array(), $sample->getTeam());
        $this->assertEquals(2, count($sample->getExploTeam()));
        try {
            $r = $sample->getTeamRoles($p_cde);
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
        }
        
        $sample->clearExploTeam();
        $this->assertEquals(array(), $sample->getExploTeam());
    }


    /**
     * test parsing hull (options etc)
     */
    public function testParsingHull()
    {
        
        // most basic form
        $sampleLines = array(
            File_Therion_Line::parse('centreline'),
            File_Therion_Line::parse('  # some stuff'),
            File_Therion_Line::parse('endcentreline'),            
        );
        $sample = File_Therion_Centreline::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        $this->assertEquals(0, count($sample)); // SPL count shots
        $this->assertEquals("", $sample->getOption("id"));
        
        // with id
        $sampleLines = array(
            File_Therion_Line::parse('centreline -id "fooID"'),
            File_Therion_Line::parse('  # some stuff'),
            File_Therion_Line::parse('endcentreline'),            
        );
        $sample = File_Therion_Centreline::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        $this->assertEquals(0, count($sample)); // SPL count shots
        $this->assertEquals("fooID", $sample->getOption("id"));
        
        // "centerline" alias
        $sampleLines = array(
            File_Therion_Line::parse('centerline'),
            File_Therion_Line::parse('  # some stuff'),
            File_Therion_Line::parse('endcenterline'),            
        );
        $sample = File_Therion_Centreline::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        $this->assertEquals(0, count($sample)); // SPL count shots
        $this->assertEquals("", $sample->getOption("id"));
    }

    /**
     * test basic parsing of simple data fields
     */
    public function testParsingMetadata()
    {
        $sampleLines = array(
            File_Therion_Line::parse('centreline'),
            File_Therion_Line::parse('  team "Foo Bar"'),
            File_Therion_Line::parse('  team "Baz Fooz" tape'),
            File_Therion_Line::parse('  '),
            File_Therion_Line::parse('  date 1997.08.10'),
            File_Therion_Line::parse('  cs UTM33 # Austria: UTM33-T'),
            File_Therion_Line::parse('  fix 1 20 40 646.23'),
            File_Therion_Line::parse('  extend ignore 2'),
            File_Therion_Line::parse('endcentreline'),            
        );
        $sample = File_Therion_Centreline::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        $team = $sample->getTeam();
        $this->assertTrue(is_array($team));
        $this->assertEquals(2, count($team));
        $this->assertInstanceOf('File_Therion_Person', $team[0]);
        $this->assertInstanceOf('File_Therion_Person', $team[1]);
        $this->assertEquals("Foo", $team[0]->getGivenname());
        $this->assertEquals("Bar", $team[0]->getSurname());
        $this->assertEquals(array(), $sample->getTeamRoles($team[0]));
        $this->assertEquals("Baz", $team[1]->getGivenname());
        $this->assertEquals("Fooz", $team[1]->getSurname());
        $this->assertEquals(array('tape'), $sample->getTeamRoles($team[1]));
        $this->assertEquals('UTM33', $sample->getCoordinateSystem());
        $this->assertEquals(
            array(
                'coords' => array(20, 40, 646.23),
                'std'    => array(0, 0, 0)
            ),
            $sample->getStations("1")->getFix()
        );
        $this->assertEquals(
            array('ignore', '2'),
            array(
                $sample->getExtends()[0]['spec'],
                $sample->getExtends()[0]['obj']->getName()
            )
        );
        
    }

    /**
     * test parsing of data part
     */
    public function testParsingShots()
    {
        $sampleLines = array(
            File_Therion_Line::parse('centreline'),
            File_Therion_Line::parse('  units compass clino grads'),
            File_Therion_Line::parse('  data normal from to compass clino tape'),
            File_Therion_Line::parse('  0     1   200       -5      6.4 '),
            File_Therion_Line::parse('  1     2    73        8      5.2 '),
            File_Therion_Line::parse('  2     3    42        0      2.09'),
            File_Therion_Line::parse('endcentreline'),            
        );
        $sample = File_Therion_Centreline::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        $this->assertEquals(3, count($sample));  // SPL count shots
        
        $shots = $sample->getShots();
        $this->assertEquals('0',  $shots[0]->getFrom()->getName());
        $this->assertEquals('1',  $shots[0]->getTo()->getName());
        $this->assertEquals(200,  $shots[0]->getBearing());
        $this->assertEquals(-5,   $shots[0]->getGradient());
        $this->assertEquals(6.4,  $shots[0]->getLength());
        
        $this->assertEquals('1',  $shots[1]->getFrom()->getName());
        $this->assertEquals('2',  $shots[1]->getTo()->getName());
        $this->assertEquals(73,   $shots[1]->getBearing());
        $this->assertEquals(8,    $shots[1]->getGradient());
        $this->assertEquals(5.2,  $shots[1]->getLength());
        
        $this->assertEquals('2',  $shots[2]->getFrom()->getName());
        $this->assertEquals('3',  $shots[2]->getTo()->getName());
        $this->assertEquals(42,   $shots[2]->getBearing());
        $this->assertEquals(0,    $shots[2]->getGradient());
        $this->assertEquals(2.09, $shots[2]->getLength());
        
    }
    
    /**
     * test parsing of data part
     */
    public function testStationNames()
    {
        $sampleLines = array(
            File_Therion_Line::parse('centreline'),
            File_Therion_Line::parse('  units compass clino grads'),
            File_Therion_Line::parse('  data normal from to compass clino tape'),
            File_Therion_Line::parse('  station-names "pre" "post"'),
            File_Therion_Line::parse('  0     1   200       -5      6.4 '),
            File_Therion_Line::parse('  1     2    73        8      5.2 '),
            File_Therion_Line::parse('  2     3    42        0      2.09'),
            File_Therion_Line::parse('endcentreline'),            
        );
        $sample = File_Therion_Centreline::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        $this->assertEquals(3, count($sample));  // SPL count shots
        $this->assertEquals(array("pre", "post"), $sample->getStationNames());        
        
        $shots = $sample->getShots();
        
        // test getting stations
        $this->assertEquals('0',  $shots[0]->getFrom()->getName());
        $this->assertEquals('1',  $shots[0]->getTo()->getName());
        $this->assertEquals('1',  $shots[1]->getFrom()->getName());
        $this->assertEquals('2',  $shots[1]->getTo()->getName());
        $this->assertEquals('2',  $shots[2]->getFrom()->getName());
        $this->assertEquals('3',  $shots[2]->getTo()->getName());
        
        // test getting explicit adjusted pre/postfixed stations
        // for this, we apply the station names
        $sample->applyStationNames();
        $this->assertEquals(array("", ""), $sample->getStationNames());
        $this->assertEquals('pre0post',  $shots[0]->getFrom()->getName());
        $this->assertEquals('pre1post',  $shots[0]->getTo()->getName());
        $this->assertEquals('pre1post',  $shots[1]->getFrom()->getName());
        $this->assertEquals('pre2post',  $shots[1]->getTo()->getName());
        $this->assertEquals('pre2post',  $shots[2]->getFrom()->getName());
        $this->assertEquals('pre3post',  $shots[2]->getTo()->getName());
        
    }
    
    /**
     * test parsing of data part
     */
    public function testParsingShotsWithSeveralDataDefinitions()
    {
        $sampleLines = array(
            File_Therion_Line::parse('centreline'),
            File_Therion_Line::parse('  units compass clino grads'),
            File_Therion_Line::parse(' data normal from to compass clino tape'),
            File_Therion_Line::parse('  0     1   200       -5      6.4'),
            File_Therion_Line::parse('  1     2    73        8      5.2'),
            File_Therion_Line::parse('  2     3    42        0      2.09'),
            File_Therion_Line::parse('   '),
            File_Therion_Line::parse(' # reading changes order, values similar'),
            File_Therion_Line::parse(' data normal to from length clino bearing'),
            File_Therion_Line::parse('  4     3   6.4       -5      200'),
            File_Therion_Line::parse('  5     4   5.2        8       73'),
            File_Therion_Line::parse('  6     5  2.09        0       42'),
            File_Therion_Line::parse('endcentreline'),            
        );
        $sample = File_Therion_Centreline::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        $this->assertEquals(6, count($sample));  // SPL count shots
        
        $shots = $sample->getShots();
        $this->assertEquals('0',  $shots[0]->getFrom()->getName());
        $this->assertEquals('1',  $shots[0]->getTo()->getName());
        $this->assertEquals(200,  $shots[0]->getBearing());
        $this->assertEquals(-5,   $shots[0]->getGradient());
        $this->assertEquals(6.4,  $shots[0]->getLength());
        
        $this->assertEquals('1',  $shots[1]->getFrom()->getName());
        $this->assertEquals('2',  $shots[1]->getTo()->getName());
        $this->assertEquals(73,   $shots[1]->getBearing());
        $this->assertEquals(8,    $shots[1]->getGradient());
        $this->assertEquals(5.2,  $shots[1]->getLength());
        
        $this->assertEquals('2',  $shots[2]->getFrom()->getName());
        $this->assertEquals('3',  $shots[2]->getTo()->getName());
        $this->assertEquals(42,   $shots[2]->getBearing());
        $this->assertEquals(0,    $shots[2]->getGradient());
        $this->assertEquals(2.09, $shots[2]->getLength());
        
        $this->assertEquals('3',  $shots[3]->getFrom()->getName());
        $this->assertEquals('4',  $shots[3]->getTo()->getName());
        $this->assertEquals(200,  $shots[3]->getBearing());
        $this->assertEquals(-5,   $shots[3]->getGradient());
        $this->assertEquals(6.4,  $shots[3]->getLength());
        
        $this->assertEquals('4',  $shots[4]->getFrom()->getName());
        $this->assertEquals('5',  $shots[4]->getTo()->getName());
        $this->assertEquals(73,   $shots[4]->getBearing());
        $this->assertEquals(8,    $shots[4]->getGradient());
        $this->assertEquals(5.2,  $shots[4]->getLength());
        
        $this->assertEquals('5',  $shots[5]->getFrom()->getName());
        $this->assertEquals('6',  $shots[5]->getTo()->getName());
        $this->assertEquals(42,   $shots[5]->getBearing());
        $this->assertEquals(0,    $shots[5]->getGradient());
        $this->assertEquals(2.09, $shots[5]->getLength());
        
    }
    
    /**
     * Parsing centreline flags
     */
    public function testParsingShotFlags()
    {
        $sampleLines = array(
            File_Therion_Line::parse('centreline'),
            File_Therion_Line::parse('  units compass clino grads'),
            File_Therion_Line::parse('  data normal from to compass clino tape'),
            File_Therion_Line::parse('  0     1   200       -5      6.4 '),
            File_Therion_Line::parse(' flags duplicate'),
            File_Therion_Line::parse('  1     2    73        8      5.2 '),
            File_Therion_Line::parse(' flags not duplicate'),
            File_Therion_Line::parse('  2     3    42        0      2.09'),
            File_Therion_Line::parse('  3     4    10       10      10'),
            File_Therion_Line::parse('  4     .    10       10      10'),
            File_Therion_Line::parse('  4     5    10       10      10'),
            File_Therion_Line::parse('  5     -    10       10      10'),
            File_Therion_Line::parse('  5     6    10       10      10'),
            File_Therion_Line::parse(' flags splay'),
            File_Therion_Line::parse('  6     7    10       10      10'),
            File_Therion_Line::parse(' flags not splay'),
            File_Therion_Line::parse('  5     6    10       10      10'),
            File_Therion_Line::parse(' flags surface'),
            File_Therion_Line::parse('  5     6    10       10      10'),
            File_Therion_Line::parse('endcentreline'),            
        );
        $sample = File_Therion_Centreline::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        
        $shots = $sample->getShots();
        $this->assertFalse($shots[0]->getFlag('duplicate'));
        $this->assertFalse($shots[0]->getFlag('splay'));
        $this->assertFalse($shots[0]->getFlag('approximate'));
        $this->assertFalse($shots[0]->getFlag('surface'));
        
        $this->assertTrue($shots[1]->getFlag('duplicate'));
        $this->assertFalse($shots[1]->getFlag('splay'));
        $this->assertFalse($shots[1]->getFlag('approximate'));
        $this->assertFalse($shots[1]->getFlag('surface'));
        
        $this->assertFalse($shots[2]->getFlag('duplicate'));
        $this->assertFalse($shots[2]->getFlag('splay'));
        $this->assertFalse($shots[2]->getFlag('approximate'));
        $this->assertFalse($shots[2]->getFlag('surface'));
        
        $this->assertFalse($shots[3]->getFlag('duplicate'));
        $this->assertFalse($shots[3]->getFlag('splay'));
        $this->assertFalse($shots[3]->getFlag('approximate'));
        $this->assertFalse($shots[3]->getFlag('surface'));
        
        $this->assertTrue($shots[4]->getFlag('duplicate')); // implicit
        $this->assertFalse($shots[4]->getFlag('splay'));
        $this->assertFalse($shots[4]->getFlag('approximate'));
        $this->assertFalse($shots[4]->getFlag('surface'));
        
        $this->assertFalse($shots[5]->getFlag('duplicate'));
        $this->assertFalse($shots[5]->getFlag('splay'));
        $this->assertFalse($shots[5]->getFlag('approximate'));
        $this->assertFalse($shots[5]->getFlag('surface'));
        
        $this->assertTrue($shots[6]->getFlag('duplicate')); // implicit
        $this->assertFalse($shots[6]->getFlag('splay'));
        $this->assertFalse($shots[6]->getFlag('approximate'));
        $this->assertFalse($shots[6]->getFlag('surface'));
        
        $this->assertFalse($shots[7]->getFlag('duplicate'));
        $this->assertFalse($shots[7]->getFlag('splay'));
        $this->assertFalse($shots[7]->getFlag('approximate'));
        $this->assertFalse($shots[7]->getFlag('surface'));
        
        $this->assertTrue($shots[8]->getFlag('duplicate')); // explicit
        $this->assertFalse($shots[8]->getFlag('splay'));
        $this->assertFalse($shots[8]->getFlag('approximate'));
        $this->assertFalse($shots[8]->getFlag('surface'));
        
        $this->assertFalse($shots[9]->getFlag('duplicate'));
        $this->assertFalse($shots[9]->getFlag('splay'));
        $this->assertFalse($shots[9]->getFlag('approximate'));
        $this->assertFalse($shots[9]->getFlag('surface'));
        
        $this->assertFalse($shots[10]->getFlag('duplicate'));
        $this->assertFalse($shots[10]->getFlag('splay'));
        $this->assertFalse($shots[10]->getFlag('approximate'));
        $this->assertTrue($shots[10]->getFlag('surface'));
    }
        
    
    /**
     * test Line generation
     */
    public function testToLinesSimple()
    {
        // simple example: hull without anything
        $sample = new File_Therion_Centreline();
        $sampleLines = $sample->toLines();
        $this->assertEquals(2, count(File_Therion_Line::filterNonEmpty($sampleLines)));
        $this->assertEquals(
            array(
                File_Therion_Line::parse('centreline'),
                File_Therion_Line::parse('endcentreline'),
            ),
            File_Therion_Line::filterNonEmpty($sampleLines)
        );
        
        // simple example: hull with options
        $sample = new File_Therion_Centreline(
            array(
                'id'   => "Foo_ID"
            )
        );
        $sampleLines = $sample->toLines();
        $this->assertEquals(2, count(File_Therion_Line::filterNonEmpty($sampleLines)));
        $this->assertEquals(
            array(
                File_Therion_Line::parse('centreline -id Foo_ID'),
                File_Therion_Line::parse('endcentreline'),
            ),
            File_Therion_Line::filterNonEmpty($sampleLines)
        );
    }
    
    /**
     * test complex Line generation
     */
    public function testToLinesComplex()
    {
        // complex centreline with several shots
        $sampleLines = array(
            File_Therion_Line::parse('centreline'),
            File_Therion_Line::parse('  date 2016'),
            File_Therion_Line::parse('  team "Beni Hallinger"'),
            File_Therion_Line::parse('  team "Foo Bar" Dog'),
            File_Therion_Line::parse('  explo-date 2015'),
            File_Therion_Line::parse('  explo-team "Benedikt Hallinger"'),
            File_Therion_Line::parse(''),
            File_Therion_Line::parse('  units compass clino grads'),
            File_Therion_Line::parse('  data normal from to compass clino tape'),
            File_Therion_Line::parse('  station-names "pre" ""'),
            File_Therion_Line::parse('  0     1   200       -5      6.4 '),
            File_Therion_Line::parse('  1     2    73        8      5.2 '),
            File_Therion_Line::parse('  2     3    42        0      2.09'),
            File_Therion_Line::parse(' flags duplicate'),
            File_Therion_Line::parse('  2     2a    30       55     5'),
            File_Therion_Line::parse(' flags not duplicate'),
            File_Therion_Line::parse('  3     4    130       -10    23.3'),
            File_Therion_Line::parse(' flags surface'),
            File_Therion_Line::parse('  4     4a    10       80    13'),
            File_Therion_Line::parse(' flags splay'),
            File_Therion_Line::parse('  4a    4splay 30       20    1'),
            File_Therion_Line::parse(' flags not splay #implicit splay follow'),
            File_Therion_Line::parse('  4a    4b    10       80    13'),
            File_Therion_Line::parse('  4a    .     60        0    0.3'),
            File_Therion_Line::parse('  4a    -    180        0    0.6'),
            File_Therion_Line::parse(' flags duplicate'),
            File_Therion_Line::parse('  4a    -    180        0    0.6'),
            File_Therion_Line::parse(' flags not duplicate'),
            File_Therion_Line::parse(' flags not surface'),
            File_Therion_Line::parse('  4    5     25       -13   130'),
            File_Therion_Line::parse('endcentreline'),            
        );
        $sample = File_Therion_Centreline::parse($sampleLines);
        
        // Check if its the same
        $lines = $sample->toLines();
        foreach ($sampleLines as $l) {print $l->toString();}
        foreach ($lines as $l) {print $l->toString();}
        //$this->assertEquals($sampleLines, $lines);
        
    }

}
?>
