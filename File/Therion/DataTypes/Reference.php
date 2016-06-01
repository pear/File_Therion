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
 * Two basic use cases exist:
 * <code>
 * // Dereference string reference to object (lookup object)
 * // (assuming structure: SurveyTop -> Subsurvey[Station_1])
 * $ref       = new File_Therion_Reference("1@Subsurvey", $topSurveyObj);
 * $station1  = $ref->getObject();
 * 
 * // Create string reference from Object
 * $ref       = new File_Therion_Reference($station1, $topSurveyObj);
 * $stringRef = $ref->toString();  // = "1@Subsurvey"
 * </code>
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
     * - (obj,    survey): Create obj reference (to generate string-reference).
     * - (string, survey): Create resolvable string-reference.
     * 
     * More special invocations are:
     * - (string, null): Create a contextless static ref (returns string as is)
     * - (obj,    null): Create fake obj reference (generates entire pathref).
     * 
     * @param string|object $refObj The object to reference
     * @param File_Therion_Survey $ctx Viewing context
     * @throws InvalidArgumentException
     * @todo better syntax check when string reference was given
     */
    public function __construct($refObj, $ctx)
    {
        if (is_a($refObj, 'File_Therion_IReferenceable')) {
            $this->_obj = $refObj;
        } elseif (is_string($refObj)) {
            if (!preg_match('/.+/i', $refObj)) {  // todo: better syntax check
                throw new InvalidArgumentException(
                "invalid string reference content '".$refObj."'!");
            }
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
     * You usually don't need to call this yourself ({@see toString()}).
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
     * a {@link File_Therion_InvalidReferenceException} will be thrown.
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
    
    /**
     * Return referenced object by string reference.
     * 
     * In case the string reference was not resolved yet, it will be
     * looked up. You usually dont need to call this yourself
     * ({@see getObject()}).
     * 
     * This will throw an UnexpectedValueException when the view context is
     * invalid. A File_Therion_InvalidReferenceException will be thrown, when
     * the referenced object cannot be found in the data model.
     * 
     * @throws UnexpectedValueException when view-context is not available
     * @throws File_Therion_InvalidReferenceException for resolving errors
     * @return File_Therion_IReferenceable object (station, etc)
     */
    public function getObject()
    {
        if (is_null($this->_obj)) {
            // resolve object from reference if not done yet.
            // (on errors this will bubble up an appropiate exception)
            $this->updateObjectReference();
        }
        
        return $this->_obj;
    }
    
    
    /**
     * Parse string reference and resolve referenced object.
     * 
     * This will take the current string reference and uses the view-context
     * to lookup the referenced object.
     * 
     * @throws UnexpectedValueException when view-context is not available
     * @throws File_Therion_InvalidReferenceException for resolving errors
     * @todo support more objects like scraps, maps etc
     */
    public function updateObjectReference()
    {
        $viewCtx = $this->_ctx;
        if (is_null($viewCtx)) {
            throw new UnexpectedValueException(
                "Cannot getObject(): View-Context"
                ." of reference is invalid!");
        }
        
        // dissolve stringref
        $parts   = explode("@", $this->_stringRef, 2);
        $id      = array_shift($parts);
        $address = explode(".", array_shift($parts));
        
        // try to find the addressed survey in substructure,
        // do this by walking down the right path
        $curCTX = $viewCtx; // assume local survey ctx as start
        while ($childAddr = array_shift($address)) {
            // select matching child survey amongst all children
            // as new context. Do this as long as there are addresses left.
            // this will essentially recurse the right path down.
            try {
                $curCTX = $curCTX->getSurveys($childAddr);
            } catch (OutOfBoundsException $e) {
                throw new File_Therion_InvalidReferenceException(
                "Could not dereference survey: ".$e->getMessage());
            }
        }
        
        // we should have reached the addressed end. As we have not met an
        // exceptional state so far, we can safely assume that we have found
        // the right addressed context.
        
        // investigate all stations of $curCTX for matching IDs
        foreach ($curCTX->getCentrelines() as $cl) {
            foreach ($cl->getStations() as $stn) {
                if ($id == $stn->getName()) {
                    $this->_obj = $stn;
                    return; // found the referenced object, go home
                }
            }
        }
        
        // TODO: Implement more referenceable objects (scraps, maps, etc)
        
        
        // Still here?: the referenced object does not exist!
        throw new File_Therion_InvalidReferenceException(
            "Could not dereference '".$this->_stringRef."': ".
            "object not found!");
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