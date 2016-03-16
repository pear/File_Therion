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
require_once 'File/Therion.php';  //includepath is loaded by phpUnit from phpunit.xml

/**
 * PHPUnit test class for File_Therion_Centreline.
 */
class File_Therion_CentrelineTest extends PHPUnit_Framework_TestCase {
    
    
    /**
     * setup test case, called before a  test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }




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
        
    }

}
?>
