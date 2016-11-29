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
        $exc = null;
        try {
            $sample = new File_Therion_Centreline("foo");
        } catch (Exception $e) {
            $exc = $e;
        }
        $this->assertInstanceOf('Exception', $exc);
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
        
    }
    
    /**
     * test fixing stations representing caves (e.g no centreline)
     */
    public function testParsingCaveFixture()
    {
        $sampleLines = array(
            File_Therion_Line::parse('centreline'),
            File_Therion_Line::parse('  date 1997.08.10'),
            File_Therion_Line::parse('  cs UTM33 # Austria: UTM33-T'),
            File_Therion_Line::parse('  fix 1 20 40 646.23'),
            File_Therion_Line::parse('endcentreline'),            
        );
        $sample = File_Therion_Centreline::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        $this->assertEquals('UTM33', $sample->getCoordinateSystem());
        $this->assertEquals(
            array(
                'coords' => array(20, 40, 646.23),
                'std'    => array(0, 0, 0)
            ),
            $sample->getStations("1")->getFix()
        );
        
    }
    
    /**
     * test basic parsing of simple data fields
     */
    public function testParsingExtends()
    {
        $sampleLines = array(
            File_Therion_Line::parse('centreline'),
            File_Therion_Line::parse('  units compass clino grads'),
            File_Therion_Line::parse('  data normal from to compass clino tape'),
            File_Therion_Line::parse('  0     1   200       -5      6.4 '),
            File_Therion_Line::parse('# note: extend command prior station,'),
            File_Therion_Line::parse('# so we can test postponed parsing'),
            File_Therion_Line::parse('  extend ignore 2'),
            File_Therion_Line::parse('  1     2    73        8      5.2 '),
            File_Therion_Line::parse('  2     3    42        0      2.09'),
            File_Therion_Line::parse('endcentreline'),            
        );
        $sample = File_Therion_Centreline::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
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
        // please note that this data is not valid in therion.
        // the station pre1post could not be connected to the centreline.
        $sampleLines = array(
            File_Therion_Line::parse('centreline'),
            File_Therion_Line::parse('  units compass clino grads'),
            File_Therion_Line::parse('  data normal from to compass clino tape'),
            File_Therion_Line::parse('  0     1   200       -5      6.4 '),
            File_Therion_Line::parse('  station-names "pre" "post"'),
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
        $this->assertEquals('0',  $shots[0]->getFrom()->getName(true));
        $this->assertEquals('1',  $shots[0]->getTo()->getName());
        $this->assertEquals('1',  $shots[0]->getTo()->getName(true));
        $this->assertEquals('1',  $shots[1]->getFrom()->getName(false));
        $this->assertEquals('pre1post',  $shots[1]->getFrom()->getName());
        $this->assertEquals('pre1post',  $shots[1]->getFrom()->getName(true));
        $this->assertEquals('2',  $shots[1]->getTo()->getName(false));
        $this->assertEquals('pre2post',  $shots[1]->getTo()->getName());
        $this->assertEquals('pre2post',  $shots[1]->getTo()->getName(true));
        $this->assertEquals('2',  $shots[2]->getFrom()->getName(false));
        $this->assertEquals('pre2post',  $shots[2]->getFrom()->getName());
        $this->assertEquals('pre2post',  $shots[2]->getFrom()->getName(true));
        $this->assertEquals('3',  $shots[2]->getTo()->getName(false));
        $this->assertEquals('pre3post',  $shots[2]->getTo()->getName());
        $this->assertEquals('pre3post',  $shots[2]->getTo()->getName(true));
        
        // test getting explicit adjusted pre/postfixed stations
        // for this, we apply the station names
        $sample->applyStationNames();
        $this->assertEquals(array("pre", "post"), $sample->getStationNames());
        // no prefix/postfix was set to this stations
        $this->assertEquals('0',  $shots[0]->getFrom()->getName());
        $this->assertEquals('0',  $shots[0]->getFrom()->getName(true));
        $this->assertEquals('1',  $shots[0]->getTo()->getName());
        $this->assertEquals('1',  $shots[0]->getTo()->getName(true));
        // these stations had a prefix/postfix
        $this->assertEquals('pre1post',  $shots[1]->getFrom()->getName());
        $this->assertEquals('pre1post',  $shots[1]->getFrom()->getName(true));
        $this->assertEquals('pre2post',  $shots[1]->getTo()->getName());
        $this->assertEquals('pre2post',  $shots[1]->getTo()->getName(true));
        $this->assertEquals('pre2post',  $shots[2]->getFrom()->getName());
        $this->assertEquals('pre2post',  $shots[2]->getFrom()->getName(true));
        $this->assertEquals('pre3post',  $shots[2]->getTo()->getName());
        $this->assertEquals('pre3post',  $shots[2]->getTo()->getName(true));
        
        // test stripping given prefix/postfix from all stations.
        // for this to work, we need to enforce a prefix/postfix throughout the
        // centreline. This also applies a station-names to the previuosly
        // unprefixed stations 0+1 of shot 0 - that means, that after stripping
        // we have an homogenous centreline naming convention.
        $sample->updateShotStationNames();
        $this->assertEquals('0',  $shots[0]->getFrom()->getName(false));
        $this->assertEquals('pre0post',  $shots[0]->getFrom()->getName());
        // this will yield expected wrong results:
        $this->assertEquals('prepre1postpost',  $shots[1]->getFrom()->getName());
        $sample->stripStationNames(); // strip them off!
        $this->assertEquals('0',  $shots[0]->getFrom()->getName(false)); // strip did nothing
        $this->assertEquals('pre0post',  $shots[0]->getFrom()->getName());
        $this->assertEquals('1',  $shots[1]->getFrom()->getName(false)); // strip worked
        $this->assertEquals('pre1post',  $shots[1]->getFrom()->getName());
        
    }
    
    /**
     * Test station names switching inside centreline
     */
     public function testStationNamesSwitching()
     {
        // complex centreline with several shots
        $sampleLines = array(
            File_Therion_Line::parse('centreline'),
            File_Therion_Line::parse('  units compass clino grads'),
            File_Therion_Line::parse('  data normal from to compass clino tape'),
            File_Therion_Line::parse('  0     1   200       -5      6.4 '),
            File_Therion_Line::parse('  station-names "pre" ""'),
            File_Therion_Line::parse('  1     2    73        8      5.2 '),
            File_Therion_Line::parse('  station-names "" "post"'),
            File_Therion_Line::parse('  2     3    42        0      2.09'),
            File_Therion_Line::parse('endcentreline')
        );
            
        $sample = File_Therion_Centreline::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        $this->assertEquals(3, count($sample));  // SPL count shots
        
        $sampleOut = $sample->toLines();
        $this->assertEquals(9, count(File_Therion_Line::filterNonEmpty($sampleLines)));
        
        // build string array for easier comparison;
        // also filter empty lines, trim and replace whitespace with fixed blank
        $in = array();
        foreach (File_Therion_Line::filterNonEmpty($sampleLines) as $sli) {
            $in[] = preg_replace('/\s+/', ' ', trim($sli->toString()));
        }
        $out = array();
        foreach (File_Therion_Line::filterNonEmpty($sampleOut) as $slo) {
            $out[] = preg_replace('/\s+/', ' ', trim($slo->toString()));
        }
        
        // adjust $in for known legal modifications
        $in[4] = preg_replace('/"(pre|post)"/', '$1', $in[4]);
        $in[6] = preg_replace('/"(pre|post)"/', '$1', $in[6]);
        
        // finally compare results
        $this->assertEquals($in, $out);

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
        
        $this->assertFalse($shots[4]->getFlag('duplicate')); 
        $this->assertTrue($shots[4]->getFlag('splay'));     // implicit
        $this->assertFalse($shots[4]->getFlag('approximate'));
        $this->assertFalse($shots[4]->getFlag('surface'));
        
        $this->assertFalse($shots[5]->getFlag('duplicate'));
        $this->assertFalse($shots[5]->getFlag('splay'));
        $this->assertFalse($shots[5]->getFlag('approximate'));
        $this->assertFalse($shots[5]->getFlag('surface'));
        
        $this->assertFalse($shots[6]->getFlag('duplicate')); 
        $this->assertTrue($shots[6]->getFlag('splay'));       // implicit
        $this->assertFalse($shots[6]->getFlag('approximate'));
        $this->assertFalse($shots[6]->getFlag('surface'));
        
        $this->assertFalse($shots[7]->getFlag('duplicate'));
        $this->assertFalse($shots[7]->getFlag('splay'));
        $this->assertFalse($shots[7]->getFlag('approximate'));
        $this->assertFalse($shots[7]->getFlag('surface'));
        
        $this->assertFalse($shots[8]->getFlag('duplicate')); 
        $this->assertTrue($shots[8]->getFlag('splay'));  // explicit
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
            File_Therion_Line::parse('  team "Foo Bar" dog'),
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
            File_Therion_Line::parse(' flags not splay'),
            File_Therion_Line::parse('  station-names "" "post"'),
            File_Therion_Line::parse('  #implicit splay shots following'),
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
        
        $sample    = File_Therion_Centreline::parse($sampleLines);
        $sampleOut = $sample->toLines();
    
        
        // build string array for easier comparison;
        // also filter empty lines, trim and replace whitespace with fixed blank
        $in = array();
        foreach (File_Therion_Line::filterNonEmpty($sampleLines) as $sli) {
            $in[] = preg_replace('/\s+/', ' ', trim($sli->toString()));
        }
        $out = array();
        foreach (File_Therion_Line::filterNonEmpty($sampleOut) as $slo) {
            $out[] = preg_replace('/\s+/', ' ', trim($slo->toString()));
        }
        
        
        /*
         * adjust $in for known legal modifications
         */
        // unescaped output
        $in[8] = preg_replace('/"(pre|post)"/', '$1', $in[8]);
        $in[21] = preg_replace('/"(pre|post)"/', '$1', $in[21]);
        
        // swapped order: station-names first, then flags
        $a=20; $b=21;   $tmp = $in[$a]; $in[$a] = $in[$b]; $in[$b] = $tmp;
        
        // changed order in header
        $a=1; $b=4;   $tmp = $in[$a]; $in[$a] = $in[$b]; $in[$b] = $tmp;
        $a=2; $b=5;   $tmp = $in[$a]; $in[$a] = $in[$b]; $in[$b] = $tmp;
        $a=3; $b=4;   $tmp = $in[$a]; $in[$a] = $in[$b]; $in[$b] = $tmp;
        $a=4; $b=5;   $tmp = $in[$a]; $in[$a] = $in[$b]; $in[$b] = $tmp;
        $a=6; $b=8;   $tmp = $in[$a]; $in[$a] = $in[$b]; $in[$b] = $tmp;
        $a=7; $b=8;   $tmp = $in[$a]; $in[$a] = $in[$b]; $in[$b] = $tmp;
        
        // combined flags instead of separate ones
        $in[27] = "flags not surface not duplicate";
        unset($in[28]);
        
        
        
        /*
         * finally compare results
         */
        $in = array_reverse(array_reverse($in)); // reassign line number keys
        $this->assertEquals($in, $out);
        
    }
    
    /**
     * Test querying stations
     */
    public function testGetStations()
    {
        $sampleLines = array(
            File_Therion_Line::parse('centreline'),
            File_Therion_Line::parse('  data normal from to compass clino tape'),
            File_Therion_Line::parse('  station-names "pre" ""'),
            File_Therion_Line::parse('  0     1   200       -5      6.4 '),
            File_Therion_Line::parse('  1     2    73        8      5.2 '),
            File_Therion_Line::parse('  fix     pre0 20 40 646.23'),
            File_Therion_Line::parse('  station pre1 "some comment" entrance'),
            File_Therion_Line::parse('endcentreline'),            
        );
        $centreline = File_Therion_Centreline::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Centreline', $centreline);
        
        $cl_st = $centreline->getStations();
        $this->assertEquals(3, count($cl_st));
        for ($i=0; $i< count($cl_st); $i++) {
            $this->assertInstanceOf('File_Therion_Station', $cl_st[$i]);
            $this->assertEquals(
                $cl_st[$i],
                $centreline->getStations("pre".strval($i))
            );
        }
        
        $this->assertTrue($cl_st[0]->isFixed());
        $this->assertEquals("", $cl_st[0]->getComment());
        $this->assertFalse($cl_st[0]->getFlag('entrance'));
        
        $this->assertFalse($cl_st[1]->isFixed());
        $this->assertEquals("some comment", $cl_st[1]->getComment());
        $this->assertTrue($cl_st[1]->getFlag('entrance'));
        
        $this->assertFalse($cl_st[2]->isFixed());
        $this->assertEquals("", $cl_st[2]->getComment());
        $this->assertFalse($cl_st[2]->getFlag('entrance'));

    }
    
    /**
     * Test fixed stations outside of shot data
     */
    public function testFixedStation()
    {
        $centreline = new File_Therion_Centreline();
        $station1   = new File_Therion_Station("1");
        $station1->setComment("Small cave");
        $station1->setFix(1, 2, 3);
        $centreline->addFixedStation($station1);
        
        $this->assertEquals(
            array(
                File_Therion_Line::parse('centreline'),
                File_Therion_Line::parse("\tstation 1 \"Small cave\""),
                File_Therion_Line::parse("\tfix 1 1 2 3 0 0 0"),
                File_Therion_Line::parse('endcentreline'),
            ),
            File_Therion_Line::filterNonEmpty($centreline->toLines())
        );
        
        
        $station2 = new File_Therion_Station("2");
        $station2->setFix(4, 5, 6, 0.5, 0.6, 0.7);
        $centreline->addFixedStation($station2);
        
        $this->assertEquals(
            array(
                File_Therion_Line::parse('centreline'),
                File_Therion_Line::parse("\tstation 1 \"Small cave\""),
                File_Therion_Line::parse("\tfix 1 1 2 3 0 0 0"),
                File_Therion_Line::parse("\tfix 2 4 5 6 0.5 0.6 0.7"),
                File_Therion_Line::parse('endcentreline'),
            ),
            File_Therion_Line::filterNonEmpty($centreline->toLines())
        );
        
        
        // clear fix of station
        $station1->clearFix();
        
        $this->assertEquals(
            array(
                File_Therion_Line::parse('centreline'),
                File_Therion_Line::parse("\tfix 2 4 5 6 0.5 0.6 0.7"),
                File_Therion_Line::parse('endcentreline'),
            ),
            File_Therion_Line::filterNonEmpty($centreline->toLines())
        );
        
        
        // clear all fixes from survey
        $centreline->clearFixedStations();
        
        $this->assertEquals(
            array(
                File_Therion_Line::parse('centreline'),
                File_Therion_Line::parse('endcentreline'),
            ),
            File_Therion_Line::filterNonEmpty($centreline->toLines())
        );
    }
    
    /**
     * Test eplicit printing of default units
     * @todo implement proper content checking
     */
    public function testExplicitUnitsPrinting()
    {
        // test data
        $shot_1 = new File_Therion_Shot('0', '1', 10, 123, 45);
        $shot_2 = new File_Therion_Shot('1', '2', 10, 231, 0);
        $shot_2->setUnit('bearing', 'degrees');
        $shot_3 = new File_Therion_Shot('2', '3', 10, 321, -45);
        $shot_3->setUnit('length',  'meters');
        $shot_3->setUnit('bearing', 'grads');
        $shot_3->setUnit('clino',   'grads');
             
        // First sample without default printing
        $centreline1 = new File_Therion_Centreline();
        $this->assertEquals(
            array(
                'length'    => null,
                'bearing'   => null,
                'gradient'  => null,
                'left'      => null,
                'right'     => null,
                'up'        => null,
                'down'      => null
            ),
            $centreline1->getUnit('all')
        );
        $centreline1->addShot($shot_1);
        $centreline1->addShot($shot_2);
        $centreline1->addShot($shot_3);
        $this->assertEquals(  // TODO: Implement proper content Checking!
            9,
            count(File_Therion_Line::filterNonEmpty($centreline1->toLines()))
        );
        
        // Now with default printing
        $centreline2 = new File_Therion_Centreline();
        $centreline2->setUnit('bearing', 'degrees');
        $centreline2->setUnit('clino', 'degrees');
        $centreline2->setUnit('length', 'm');
        $this->assertEquals(
            array(
                'length'    => new File_Therion_Unit(null, 'm'),
                'bearing'   => new File_Therion_Unit(null, 'degrees'),
                'gradient'  => new File_Therion_Unit(null, 'degrees'),
                'left'      => null,
                'right'     => null,
                'up'        => null,
                'down'      => null
            ),
            $centreline2->getUnit('all')
        );
        $centreline2->addShot($shot_1);
        $centreline2->addShot($shot_2);
        $centreline2->addShot($shot_3);
        $this->assertEquals(  // TODO: Implement proper content Checking!
            10,
            count(File_Therion_Line::filterNonEmpty($centreline2->toLines()))
        );
    }
    
    /**
     * Test parsing of grade
     */
    public function testParsingGrade()
    {
        // with string as grade
        $sampleLines = array(
            File_Therion_Line::parse('centreline'),
            File_Therion_Line::parse('  grade BCRA5'),
            File_Therion_Line::parse('  units compass clino grads'),
            File_Therion_Line::parse('  data normal from to compass clino tape'),
            File_Therion_Line::parse('  0     1   200       -5      6.4 '),
            File_Therion_Line::parse('  1     2    73        8      5.2 '),
            File_Therion_Line::parse('  2     3    42        0      2.09'),
            File_Therion_Line::parse('endcentreline'),            
        );
        $sample = File_Therion_Centreline::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        $this->assertEquals(1, count($sample->getGrade()));
        $this->assertEquals(
            array(new File_Therion_Grade("BCRA5")),
            $sample->getGrade()
        );
        
        // with several strings as grade
        $sampleLines = array(
            File_Therion_Line::parse('centreline'),
            File_Therion_Line::parse('  grade BCRA5 test'),
            File_Therion_Line::parse('  units compass clino grads'),
            File_Therion_Line::parse('  data normal from to compass clino tape'),
            File_Therion_Line::parse('  0     1   200       -5      6.4 '),
            File_Therion_Line::parse('  1     2    73        8      5.2 '),
            File_Therion_Line::parse('  2     3    42        0      2.09'),
            File_Therion_Line::parse('endcentreline'),            
        );
        $sample = File_Therion_Centreline::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Centreline', $sample);
        $this->assertEquals(2, count($sample->getGrade()));
        $this->assertEquals(
            array(new File_Therion_Grade("BCRA5"), new File_Therion_Grade("test")),
            $sample->getGrade()
        );
    }
    
    /**
     * Test generation of grade references
     * 
     * @todo the check of the generated lines is heavily dependent on the centerline-data format generated internally.
     */
    public function testGeneratingGrade()
    {
        // with string as grade
        $centreline1 = new File_Therion_Centreline();
        $centreline1->addShot(new File_Therion_Shot('0', '1', 10, 123, 45));
        $centreline1->setGrade('BCRA5');
        $this->assertEquals(
            array(
                File_Therion_Line::parse('centreline'),
                File_Therion_Line::parse("\tgrade BCRA5"),
                File_Therion_Line::parse("\tdata\tnormal\tfrom\tto\tlength\tbearing\tgradient\tleft\tright\tup\tdown"),
                File_Therion_Line::parse("\t\t\t0\t1\t10\t123\t45\t0\t0\t0\t0"),
                File_Therion_Line::parse('endcentreline')
            ),
            File_Therion_Line::filterNonEmpty($centreline1->toLines())
        );
        
        
        // with empty grade (reset centreline1)
        $centreline1->setGrade('');
        $this->assertEquals(
            array(
                File_Therion_Line::parse('centreline'),
                File_Therion_Line::parse("\tdata\tnormal\tfrom\tto\tlength\tbearing\tgradient\tleft\tright\tup\tdown"),
                File_Therion_Line::parse("\t\t\t0\t1\t10\t123\t45\t0\t0\t0\t0"),
                File_Therion_Line::parse('endcentreline')
            ),
            File_Therion_Line::filterNonEmpty($centreline1->toLines())
        );
        
        
        // with object as grade
        $grade = new File_Therion_Grade('test');
        $centreline2 = new File_Therion_Centreline();
        $centreline2->addShot(new File_Therion_Shot('0', '1', 10, 123, 45));
        $centreline2->setGrade($grade);
        $this->assertEquals(
            array(
                File_Therion_Line::parse('centreline'),
                File_Therion_Line::parse("\tgrade test"),
                File_Therion_Line::parse("\tdata\tnormal\tfrom\tto\tlength\tbearing\tgradient\tleft\tright\tup\tdown"),
                File_Therion_Line::parse("\t\t\t0\t1\t10\t123\t45\t0\t0\t0\t0"),
                File_Therion_Line::parse('endcentreline')
            ),
            File_Therion_Line::filterNonEmpty($centreline2->toLines())
        );
        
    }

}
?>