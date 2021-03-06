<?php
/**
* Global Search Engine for Moodle
*
* @package search
* @category core
* @subpackage document_wrappers
* @author Michael Campanis (mchampan) [cynnical@gmail.com], Valery Fremaux [valery.fremaux@club-internet.fr] > 1.8
* @date 2008/03/31
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @version Moodle 2.0
*
* document handling for wiki activity module
* This file contains the mapping between a wiki page and it's indexable counterpart,
* e.g. searchdocument->title = wikipage->pagename
*
* Functions for iterating and retrieving the necessary records are now also included
* in this file, rather than mod/wiki/lib.php
*/

/**
* includes and requires
*/
require_once($CFG->dirroot.'/search/documents/document.php');
require_once($CFG->dirroot.'/mod/wiki/lib.php');

/**
* All the $doc->___ fields are required by the base document class!
* Each and every module that requires search functionality must correctly
* map their internal fields to the five $doc fields (id, title, author, contents
* and url). Any module specific data can be added to the $data object, which is
* serialised into a binary field in the index.
*/
class WikiSearchDocument extends SearchDocument {
    public function __construct(&$page, $wiki_id, $course_id, $group_id, $user_id, $context_id) {
        // generic information; required
        $doc->docid         = $page['id'];
        $doc->documenttype  = SEARCH_TYPE_WIKI;
        $doc->itemtype      = 'standard';
        $doc->contextid     = $context_id;

        $doc->title     = $page['pagename'];
        $doc->date      = $page['lastmodified'];
        //remove '(ip.ip.ip.ip)' from wiki author field
        $doc->author    = preg_replace('/\(.*?\)/', '', $page['author']);
        $doc->contents  = $page['content'];
        $doc->url       = wiki_make_link($wiki_id, $page['pagename'], $page['version']);
        
        // module specific information; optional
        $data->version  = $page['version'];
        $data->wiki     = $wiki_id;
        
        // construct the parent class
        parent::__construct($doc, $data, $course_id, $group_id, $user_id, 'mod/'.SEARCH_TYPE_WIKI);
    } 
}

/**
* converts a page name to cope Wiki constraints. Transforms spaces in plus.
* @param str the name to convert
* @return the converted name
*/
function wiki_name_convert($str) {
    return str_replace(' ', '+', $str);
}

/**
* constructs a valid link to a wiki content
* @param int $wikiId
* @param string $title
* @param int $version
* @uses $CFG
*/
function wiki_make_link($wikiId, $title, $version) {
    global $CFG;

    return $CFG->wwwroot.'/mod/wiki/view.php?wid='.$wikiId.'&amp;page='.wiki_name_convert($title).'&amp;version='.$version;
}

/**
* rescued and converted from ewikimoodlelib.php
* retrieves latest version of a page
* @uses $DB
* @param object $entry the wiki object as a reference
* @param string $pagename the name of the page known by the wiki engine
* @param int $version
*/
function wiki_get_latest_page(&$entry, $pagename, $version = 0) {
    global $DB;
    
    $pagename = "'".addslashes($pagename)."'";
    
    if ($version > 0 && is_int($version)) {
        $versionclause = "AND ( version = '$version' )";
    } else {
        $versionclause = '';
    } 
    
    $select = "( pagename = ':pagename' ) AND wiki = :wikiid :versionclause ";
    $sort   = 'version DESC';
    
    //change this to recordset_select, as per http://docs.moodle.org/en/Datalib_Notes
    if ($result_arr = $DB->get_records_select('wiki_pages', $select, array('pagename' => $pagename, 'wikiid' => $entry->id, 'versionclause' => $versionclause), $sort, '*', 0, 1)) {
        foreach ($result_arr as $obj) {
            $result_obj = $obj;
        } 
    } 
    
    if (isset($result_obj))  {
        $result_obj->meta = @unserialize($result_obj->meta);
        return $result_obj;
    } else {
        return false;
    } 
}

/**
* fetches all pages, including old versions
* @uses $DB
* @param object $entry the wiki object as a reference
* @return an array of record objects that represents pages of this wiki object
*/
function wiki_get_pages(&$entry) {
    global $DB;
    
    return $DB->get_records('wiki_pages', array('wiki', $entry->id));
}

/**
* fetches all the latest versions of all the pages
* @uses $DB
* @param reference $entry
*/
function wiki_get_latest_pages(&$entry) {
  global $DB;
    
  //== (My)SQL for this
  /* select * from wiki_pages
     inner join
    (select wiki_pages.pagename, max(wiki_pages.version) as ver
    from wiki_pages group by pagename) as a
    on ((wiki_pages.version = a.ver) and
    (wiki_pages.pagename like a.pagename)) */

    $pages = array();
    
    //http://moodle.org/bugs/bug.php?op=show&bugid=5877&pos=0
    if ($ids = $DB->get_records('wiki_pages', array('wiki' => $entry->id), '', 'distinct pagename')) {
        if ($pagesets = $DB->get_records('wiki_pages', array('wiki' => $entry->id), '', 'distinct pagename')) {
            foreach ($pagesets as $aPageset) {
                $pages[] = wiki_get_latest_page($entry, $aPageset->pagename);
            } 
        } else {
            return false;
        } 
    }
    return $pages;
}

/**
* part of search engine API
* @uses $DB;
*
*/
function wiki_iterator() {
    global $DB;

    $wikis = $DB->get_records('wiki');
    return $wikis;
}

/**
* part of search engine API
* @uses $DB
* @param reference $wiki a wiki instance
* @return an array of searchable deocuments
*/
function wiki_get_content_for_index(&$wiki) {
    global $DB;

    $documents = array();
    $entries = wiki_get_entries($wiki);
    if ($entries){
        $coursemodule = $DB->get_field('modules', 'id', array('name' => 'wiki'));
        $cm = $DB->get_record('course_modules', array('course' => $wiki->course, 'module' => $coursemodule, 'instance' => $wiki->id));
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        foreach($entries as $entry) {
    
            //all pages
            //$pages = wiki_get_pages($entry);
            
            //latest pages
            $pages = wiki_get_latest_pages($entry);
            if (is_array($pages)) {
                foreach($pages as $page) {
                    if (strlen($page->content) > 0) {
                        $documents[] = new WikiSearchDocument(get_object_vars($page), $entry->wikiid, $entry->course, $entry->groupid, $page->userid, $context->id);
                    } 
                } 
            } 
        } 
    }
    return $documents;
}

/**
* returns a single wiki search document based on a wiki_entry id
* @uses $DB;
* @param int $id the id of the wiki
* @param string $itemtype the type of information (standard)
* @return a searchable document
*/
function wiki_single_document($id, $itemtype) {
    global $DB;
    
    $page = $DB->get_record('wiki_pages', array('id' => $id));
    $entry = $DB->get_record('wiki_entries', array('id' => $page->wiki));
    $coursemodule = $DB->get_field('modules', 'id', array('name' => 'wiki'));
    $cm = $DB->get_record('course_modules', array('course' => $entry->course, 'module' => $coursemodule, 'instance' => $entry->wikiid));
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    return new WikiSearchDocument(get_object_vars($page), $entry->wikiid, $entry->course, $entry->groupid, $page->userid, $context->id);
}

/**
* dummy delete function that packs id with itemtype.
* this was here for a reason, but I can't remember it at the moment.
*
*/
function wiki_delete($info, $itemtype) {
    $object->id = $info;
    $object->itemtype = $itemtype;
    return $object;
}

//returns the var names needed to build a sql query for addition/deletions
function wiki_db_names() {
    //[primary id], [table name], [time created field name], [time modified field name]
    return array(array('id', 'wiki_pages', 'created', 'lastmodified', 'standard'));
}

/**
* this function handles the access policy to contents indexed as searchable documents. If this 
* function does not exist, the search engine assumes access is allowed.
* When this point is reached, we already know that : 
* - user is legitimate in the surrounding context
* - user may be guest and guest access is allowed to the module
* - the function may perform local checks within the module information logic
* @param string $path the access path to the module script code
* @param string $itemtype the information subclassing (usefull for complex modules, defaults to 'standard')
* @param int $this_id the item id within the information class denoted by itemtype. In wikies, this id 
* points out the indexed wiki page.
* @param object $user the user record denoting the user who searches
* @param int $group_id the current group used by the user when searching
* @param int $context_id a context that eventually comes with the object
* @uses $CFG, $DB
* @return true if access is allowed, false elsewhere
*/
function wiki_check_text_access($path, $itemtype, $this_id, $user, $group_id, $context_id){
    global $CFG, $DB;
    
    // get the wiki object and all related stuff
    $page = $DB->get_record('wiki_pages', array('id' => $id));
    $entry = $DB->get_record('wiki_entries', array('id' => $page->wiki));
    $course = $DB->get_record('course', array('id' => $entry->course));
    $context = $DB->get_record('context', array('id' => $context_id));
    $cm = $DB->get_record('course_modules', array('id' => $context->instanceid));
    if (!$cm->visible && !has_capability('moodle/course:viewhiddenactivities', $context)) {
        if (!empty($CFG->search_access_debug)) echo "search reject : hidden wiki ";
        return false;
    }
    
    //group consistency check : checks the following situations about groups
    // trap if user is not same group and groups are separated
    $current_group = get_current_group($course->id);
    if ((groupmode($course) == SEPARATEGROUPS) && $group_id != $current_group && !has_capability('moodle/site:accessallgroups', $context)) {
        if (!empty($CFG->search_access_debug)) echo "search reject : separated group owner wiki ";
        return false;
    }
        
    return true;
}

/**
* this call back is called when displaying the link for some last post processing
*
*/
function wiki_link_post_processing($title){
    global $CFG;
    
    if ($CFG->block_search_utf8dir){
        return mb_convert_encoding($title, 'UTF-8', 'auto');
    }
    return mb_convert_encoding($title, 'auto', 'UTF-8');
}

?>