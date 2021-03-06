<?php // $Id$

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 1999 onwards  Martin Dougiamas  http://moodle.com       //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////
/**
 * File containing the grade_report class.
 * @package gradebook
 */

require_once($CFG->libdir.'/gradelib.php');

/**
 * An abstract class containing variables and methods used by all or most reports.
 * @abstract
 * @package gradebook
 */
abstract class grade_report {
    /**
     * The courseid.
     * @var int $courseid
     */
    public $courseid;

    /**
     * The course.
     * @var object $course
     */
    public $course;

    /** Grade plugin return tracking object.
     * @var object $gpr
     */
    public $gpr;

    /**
     * The context.
     * @var int $context
     */
    public $context;

    /**
     * The grade_tree object.
     * @var object $gtree
     */
    public $gtree;

    /**
     * User preferences related to this report.
     * @var array $prefs
     */
    public $prefs = array();

    /**
     * The roles for this report.
     * @var string $gradebookroles
     */
    public $gradebookroles;

    /**
     * base url for sorting by first/last name.
     * @var string $baseurl
     */
    public $baseurl;

    /**
     * base url for paging.
     * @var string $pbarurl
     */
    public $pbarurl;

    /**
     * Current page (for paging).
     * @var int $page
     */
    public $page;

    /**
     * Array of cached language strings (using get_string() all the time takes a long time!).
     * @var array $lang_strings
     */
    public $lang_strings = array();

//// GROUP VARIABLES (including SQL)

    /**
     * The current group being displayed.
     * @var int $currentgroup
     */
    public $currentgroup;

    /**
     * Current course group mode
     * @var int $groupmode
     */
    var $groupmode;

    /**
     * A HTML select element used to select the current group.
     * @var string $group_selector
     */
    public $group_selector;

    /**
     * An SQL fragment used to add linking information to the group tables.
     * @var string $groupsql
     */
    protected $groupsql;

    /**
     * An SQL constraint to append to the queries used by this object to build the report.
     * @var string $groupwheresql
     */
    protected $groupwheresql;

    /**
     * The ordered params for $groupwheresql
     * @var array $groupwheresql_params
     */
    protected $groupwheresql_params = array();


    /**
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     * @param int $courseid
     * @param object $gpr grade plugin return tracking object
     * @param string $context
     * @param int $page The current page being viewed (when report is paged)
     */
    public function __construct($courseid, $gpr, $context, $page=null) {
        global $CFG, $COURSE, $DB;

        if (empty($CFG->gradebookroles)) {
            print_error('norolesdefined', 'grades');
        }


        $this->courseid  = $courseid;
        if ($this->courseid == $COURSE->id) {
            $this->course = $COURSE;
        } else {
            $this->course = $DB->get_record('course', array('id' => $this->courseid));
        }

        $this->gpr       = $gpr;
        $this->context   = $context;
        $this->page      = $page;

        // roles to be displayed in the gradebook
        $this->gradebookroles = $CFG->gradebookroles;

        // Set up link to preferences page
        $this->preferences_page = $CFG->wwwroot.'/grade/report/grader/preferences.php?id='.$courseid;

        // init gtree in child class
    }

    /**
     * Given the name of a user preference (without grade_report_ prefix), locally saves then returns
     * the value of that preference. If the preference has already been fetched before,
     * the saved value is returned. If the preference is not set at the User level, the $CFG equivalent
     * is given (site default).
     * @static (Can be called statically, but then doesn't benefit from caching)
     * @param string $pref The name of the preference (do not include the grade_report_ prefix)
     * @param int $objectid An optional itemid or categoryid to check for a more fine-grained preference
     * @return mixed The value of the preference
     */
    public function get_pref($pref, $objectid=null) {
        global $CFG;
        $fullprefname = 'grade_report_' . $pref;
        $shortprefname = 'grade_' . $pref;

        $retval = null;

        if (!isset($this) OR get_class($this) != 'grade_report') {
            if (!empty($objectid)) {
                $retval = get_user_preferences($fullprefname . $objectid, grade_report::get_pref($pref));
            } elseif (isset($CFG->$fullprefname)) {
                $retval = get_user_preferences($fullprefname, $CFG->$fullprefname);
            } elseif (isset($CFG->$shortprefname)) {
                $retval = get_user_preferences($fullprefname, $CFG->$shortprefname);
            } else {
                $retval = null;
            }
        } else {
            if (empty($this->prefs[$pref.$objectid])) {

                if (!empty($objectid)) {
                    $retval = get_user_preferences($fullprefname . $objectid);
                    if (empty($retval)) {
                        // No item pref found, we are returning the global preference
                        $retval = $this->get_pref($pref);
                        $objectid = null;
                    }
                } else {
                    $retval = get_user_preferences($fullprefname, $CFG->$fullprefname);
                }
                $this->prefs[$pref.$objectid] = $retval;
            } else {
                $retval = $this->prefs[$pref.$objectid];
            }
        }

        return $retval;
    }

    /**
     * Uses set_user_preferences() to update the value of a user preference. If 'default' is given as the value,
     * the preference will be removed in favour of a higher-level preference.
     * @static
     * @param string $pref_name The name of the preference.
     * @param mixed $pref_value The value of the preference.
     * @param int $itemid An optional itemid to which the preference will be assigned
     * @return bool Success or failure.
     */
    public function set_pref($pref, $pref_value='default', $itemid=null) {
        $fullprefname = 'grade_report_' . $pref;
        if ($pref_value == 'default') {
            return unset_user_preference($fullprefname.$itemid);
        } else {
            return set_user_preference($fullprefname.$itemid, $pref_value);
        }
    }

    /**
     * Handles form data sent by this report for this report. Abstract method to implement in all children.
     * @abstract
     * @param array $data
     * @return mixed True or array of errors
     */
    abstract function process_data($data);

    /**
     * Processes a single action against a category, grade_item or grade.
     * @param string $target Sortorder
     * @param string $action Which action to take (edit, delete etc...)
     * @return
     */
    abstract function process_action($target, $action);

    /**
     * First checks the cached language strings, then returns match if found, or uses get_string()
     * to get it from the DB, caches it then returns it.
     * @param string $strcode
     * @param string $section Optional language section
     * @return string
     */
    public function get_lang_string($strcode, $section=null) {
        if (empty($this->lang_strings[$strcode])) {
            $this->lang_strings[$strcode] = get_string($strcode, $section);
        }
        return $this->lang_strings[$strcode];
    }

    /**
     * Fetches and returns a count of all the users that will be shown on this page.
     * @param boolean $groups include groups limit
     * @return int Count of users
     */
    public function get_numusers($groups=true) {
        global $CFG, $DB;

        $groupsql      = "";
        $groupwheresql = "";
        list($usql, $params) = $DB->get_in_or_equal(explode(',', $this->gradebookroles), SQL_PARAMS_NAMED, 'grbr0');

        if ($groups) {
            $groupsql      = $this->groupsql;
            $groupwheresql = $this->groupwheresql;
            $params        = array_merge($params, $this->groupwheresql_params);
        }

        $countsql = "SELECT COUNT(DISTINCT u.id)
                       FROM {user} u
                            JOIN {role_assignments} ra ON u.id = ra.userid
                            $groupsql
                      WHERE ra.roleid $usql AND u.deleted = 0
                            $groupwheresql
                            AND ra.contextid ".get_related_contexts_string($this->context);
        return $DB->count_records_sql($countsql, $params);
    }

    /**
     * Sets up this object's group variables, mainly to restrict the selection of users to display.
     */
    protected function setup_groups() {
        /// find out current groups mode
        if ($this->groupmode = groups_get_course_groupmode($this->course)) {
            $this->currentgroup = groups_get_course_group($this->course, true);
            $this->group_selector = groups_print_course_menu($this->course, $this->pbarurl, true);

            if ($this->groupmode == SEPARATEGROUPS and !$this->currentgroup and !has_capability('moodle/site:accessallgroups', $this->context)) {
                $this->currentgroup = -2; // means can not accesss any groups at all
            }

            if ($this->currentgroup) {
                $this->groupsql             = " JOIN {groups_members} gm ON gm.userid = u.id ";
                $this->groupwheresql        = " AND gm.groupid = :gr_grpid ";
                $this->groupwheresql_params = array('gr_grpid'=>$this->currentgroup);
            }
        }
    }

    /**
     * Returns an arrow icon inside an <a> tag, for the purpose of sorting a column.
     * @param string $direction
     * @param string $sort_link
     * @param string HTML
     */
    protected function get_sort_arrow($direction='move', $sort_link=null) {
        $matrix = array('up' => 'desc', 'down' => 'asc', 'move' => 'desc');
        $strsort = $this->get_lang_string('sort' . $matrix[$direction]);

        $arrow = print_arrow($direction, $strsort, true);
        $html = '<a href="'.$sort_link .'" title="'.$strsort.'">' . $arrow . '</a>';
        return $html;
    }
}
?>
