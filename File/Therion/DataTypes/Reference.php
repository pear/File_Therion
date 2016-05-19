<?php
/**
 * Therion datatype class.
 *
 * PHP version 5
 *
 * @category   file
 * @package    File_Therion_DataTypes
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */

/**
 * Class representing a therion object reference.
 * 
 * Some objects like Stations, scraps, etc. can be referenced. An example is an
 * equate command linking two stations together. If the station is not locally
 * known, it must be referenced and resolved. Therion provides a special syntax
 * for this: "<ID>@<Survey>.<SubSurvey>", so an object can be referenced in
 * upper survey structures. Note that it is not possible to reference objects
 * in parent surveys, only lower levels may be referenced.
 * 
 * A Reference-object implements a specific object as viewed from some context.
 * 
 * The main intention of this class is PACKAGE INTERNAL USE, to make dealing
 * parsing/resolving references easy. Under normal circumstances you should
 * not need to use references yourself. The package will create them
 * automatically when neccessary.
 *
 * @category   file
 * @package    File_Therion_DataTypes
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Reference
    implements File_Therion_DataType
{
    
    /**
     * Viewing context.
     * 
     * @var File_Therion_Survey survey context
     */
    protected $_ctx = null;
    
    /**
     * The referenced object.
     * 
     * @var File_Therion_Survey survey context
     */
    protected $_obj = null;
    
    /**
     * The string representation of the reference.
     * 
     * @var File_Therion_Survey survey context
     */
    protected $_stringRef = "";
    
    
    /**
     * Create a new therion reference object.
     * 
     * This generates a new reference object. The following invocations exist:
     * - (obj,    survey): Create resolvable reference.
     * - (obj,    null):   Create fake reference (resolves obj's entire path).
     * - (string, survey): Create resolvable reference.
     * - (string, null):   Create a contextless ref (returns string as is)

     * 
     * @param string|object $refObj The object to reference
     * @param File_Therion_Survey $ctx Viewing context
     * @throws InvalidArgumentException
     * @todo syntax check on stringref
     */
    public function __construct($refObj, $ctx)
    {
        if (is_a($refObj, 'File_Therion_IReferenceable')) {
            $this->_obj = $refObj;
        } elseif (is_string($refObj)) {
            // todo: check for syntax
            $this->_stringRef = $refObj;
            
        } else {
            throw new InvalidArgumentException(
                'invalid $refObj datatype!');
        }
        
        if (is_null($ctx) || is_a($ctx, 'File_Therion_Survey')) {
            $this->_ctx = $ctx;
        } else {
            throw new InvalidArgumentException(
                'invalid $ctx datatype!');
        }
    }
    
    
    /**
     * Get string representation.
     * 
     * The string reference will be generated in case it was not already
     * present. To be sure you always have an up-to-date reference, you
     * may call {@link updateStringReference()} manually beforehand.
     *
     * @return Therion compliant String of this reference
     */
    public function toString()
    {
        if (!$this->_stringRef) {
            $this->updateStringReference();
        }
        return $this->_stringRef;
    }


    /**
     * Parse string reference into static reference object.
     * 
     * This will create a basic string reference object without context.
     * As such, it is just a kind of alias to the constructor for static
     * string references.
     * 
     * @param $string reference to parse
     * @return File_Therion_Reference
     */
    public static function parse($string)
    {
        $ref = new File_Therion_Reference($string, null);
        return $ref;
    }
    
        
    /**
     * Update string representation of this reference.
     * 
     * This will generate a string reference that will be returned by
     * {@link toString()}. The reference addresses the current referenced object
     * viewed from the reference view-context.
     * 
     * @return void
     */
    public function updateStringReference()
    {        
        $objName = $this->_obj->getName();
        $this->_stringRef = $objName;
        
        $resolvedPath = $this->getSurveyPath(true);
        if (count($resolvedPath) > 0) {
            $names = array();
            foreach ($resolvedPath as $sp) {
                $names[] = $sp->getName();
            }
            $this->_stringRef .= "@".implode('.', $names);
        }
    }
    
    
    /**
     * Retrieves the path leading to the objects survey context.
     * 
     * Objects must be associated to a survey. The surveys of the Object must be
     * reachable from the survey context of the reference (viewing ctx), which
     * is usually the parent survey referencing station of subsurveys.
     * 
     * When the references viewing context is not set (null), the topmost Object
     * context will be used as top parent. This will result in the maximum
     * possible path being resolved.
     * When the Objects context is null, an UnexpectedValueException is thrown.
     * When the Objects context is not reachable from the reference context, 
     * a File_Therion_InvalidReferenceException will be thrown.
     * 
     * Technically the search is done by walking the objects survey context
     * structure upwards until the viewing survey context is reached.
     * 
     * The array returned is one of the following:
     * - array containing no elements: Object is local to viewing context
     * - array containing n elements: top-down path of surveys
     * 
     * @return array indexed array with survey objects representing survey path
     * @throws UnexpectedValueException when context is not available
     * @throws File_Therion_InvalidReferenceException for resolving errors
     */
    public function getSurveyPath()
    {
        $resPath = array();
        
        $obj     = $this->_obj;
        $objCtx  = $obj->getSurveyContext();
        $viewCtx = $this->_ctx;
        
        if (is_null($objCtx)) {
            throw new UnexpectedValueException(
                "Referenced object has no survey context");
        }
        if (is_null($viewCtx)) {
            // Fake resolving requested!
            // this will be handled in the loop below.
        }
        
        // walk parents upwards until survey contexts match by-name
        $parent = $objCtx; // compare local contexts first
        while (!is_null($parent)) {
            if (!is_null($viewCtx)
                && $parent->getName() == $viewCtx->getName()) {
                // station ctx == equals ctx
                //   stop searching, reached target context: go home
                return $resPath;
                
            } else {
                // ctx does not match OR parent ctx is valid but view-ctx=null
                //   add the survey as parent to the resolved path
                array_unshift($resPath, $parent);
            }
          
            // get parents parent for next loop run
            $parent = $parent->getParent(); 
        }
        
        // We made it past the resolving loop, eg we reached the TOP of the
        // parent structure of the referenced object.
        // This is only a valid result if there was no valid equate context,
        // otherwise this means that the refObjs context structure is not
        // reachable from the viewing survey context.
        if (is_null($viewCtx)) {
            // return valid result (=all survey parents of referenced object)
            return $resPath;
            
        } else {
            // no context match but valid viewCtx: resolving failed
            throw new File_Therion_InvalidReferenceException(
                "could not resolve object ".$obj->getName()
                ." from survey ctx ".$viewCtx->getName());
        }
        
    }
    
    
    
}



/**
 * Interface defining referenceable objects.
 * 
 * Such objects may be used in the reference class.
 *
 * @category   file
 * @package    File_Therion_DataTypes
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
interface File_Therion_IReferenceable
{
    /**
     * Get name of object.
     * 
     * @return string
     */
    public function getName();
    
    /**
     * Get survey context.
     * 
     * @return File_Therion_Survey
     */
    public function getSurveyContext();
}

?>