<?php
/**
 * Library functions for using AJAX with Moodle.
 */

/**
 * Get the path to a JavaScript library.
 * @param $libname - the name of the library whose path we need.
 * @return string
 */
function ajax_get_lib($libname) {
    global $CFG, $HTTPSPAGEREQUIRED;

    $libpath = '';
    $external_yui = false;

    $translatelist = array(
            'yui_yahoo' => '/lib/yui/yahoo/yahoo-min.js',
            'yui_animation' => '/lib/yui/animation/animation-min.js',
            'yui_autocomplete' => '/lib/yui/autocomplete/autocomplete-min.js',
            'yui_button' => '/lib/yui/button/button-min.js',
            'yui_calendar' => '/lib/yui/calendar/calendar-min.js',
            'yui_charts' => '/lib/yui/charts/charts-min.js',
            'yui_colorpicker' => '/lib/yui/colorpicker/colorpicker-min.js',
            'yui_connection' => '/lib/yui/connection/connection-min.js',
            'yui_container' => '/lib/yui/container/container-min.js',
            'yui_cookie' => '/lib/yui/cookie/cookie-min.js',
            'yui_datasource' => '/lib/yui/datasource/datasource-min.js',
            'yui_datatable' => '/lib/yui/datatable/datatable-min.js',
            'yui_dom' => '/lib/yui/dom/dom-min.js',
            'yui_dom-event' => '/lib/yui/yahoo-dom-event/yahoo-dom-event.js',
            'yui_dragdrop' => '/lib/yui/dragdrop/dragdrop-min.js',
            'yui_editor' => '/lib/yui/editor/editor-min.js',
            'yui_element' => '/lib/yui/element/element-min.js',
            'yui_event' => '/lib/yui/event/event-min.js',
            'yui_get' => '/lib/yui/get/get-min.js',
            'yui_history' => '/lib/yui/history/history-min.js',
            'yui_imagecropper' => '/lib/yui/imagecropper/imagecropper-min.js',
            'yui_imageloader' => '/lib/yui/imageloader/imageloader-min.js',
            'yui_json' => '/lib/yui/json/json-min.js',
            'yui_layout' => '/lib/yui/layout/layout-min.js',
            'yui_logger' => '/lib/yui/logger/logger-min.js',
            'yui_menu' => '/lib/yui/menu/menu-min.js',
            'yui_profiler' => '/lib/yui/profiler/profiler-min.js',
            'yui_profilerviewer' => '/lib/yui/profilerviewer/profilerviewer-min.js',
            'yui_resize' => '/lib/yui/resize/resize-min.js',
            'yui_selector' => '/lib/yui/selector/selector-min.js',
            'yui_simpleeditor' => '/lib/yui/editor/simpleeditor-min.js',
            'yui_slider' => '/lib/yui/slider/slider-min.js',
            'yui_tabview' => '/lib/yui/tabview/tabview-min.js',
            'yui_treeview' => '/lib/yui/treeview/treeview-min.js',
            'yui_uploader' => '/lib/yui/uploader/uploader-min.js',
            'yui_utilities' => '/lib/yui/utilities/utilities.js',
            'yui_yuiloader' => '/lib/yui/yuiloader/yuiloader-min.js',
            'yui_yuitest' => '/lib/yui/yuitest/yuitest-min.js',
            'ajaxcourse_blocks' => '/lib/ajax/block_classes.js',
            'ajaxcourse_sections' => '/lib/ajax/section_classes.js',
            'ajaxcourse' => '/lib/ajax/ajaxcourse.js'
            );

    if (!empty($HTTPSPAGEREQUIRED)) {
        $wwwroot = $CFG->httpswwwroot;
    } else {
        $wwwroot = $CFG->wwwroot;
    }

    if (array_key_exists($libname, $translatelist)) {
        // If this is a YUI file and we are using external libraries
        if (substr($libname, 0, 3) == 'yui' && !empty($CFG->useexternalyui)) {
            $external_yui = true;
            // Get current version
            include($CFG->libdir.'/yui/version.php');
            $libpath = 'http://yui.yahooapis.com/'.$yuiversion.'/build/'.substr($translatelist[$libname], 9);
        } else {
            $libpath = $wwwroot . $translatelist[$libname];
        }

        // If we are in developer debug mode, use the non-compressed version of YUI for easier debugging.
        if (debugging('', DEBUG_DEVELOPER)) {
            $libpath = str_replace('-min.js', '.js', $libpath);
        }

    } else if (preg_match('/^https?:/', $libname)) {
        $libpath = $libname;

    } else {
        $libpath = $wwwroot . '/' . $libname;
    }

    // Make sure the file exists if it is local.
    if ($external_yui === false) {
        $testpath = str_replace($wwwroot, $CFG->dirroot, $libpath);
        if (!file_exists($testpath)) {
            throw new moodle_exception('unknownjsinrequirejs', '', '', $libpath);
        }
    }

    return $libpath;
}


/**
 * Returns whether ajax is enabled/allowed or not.
 */
function ajaxenabled($browsers = array()) {

    global $CFG, $USER;

    if (!empty($browsers)) {
        $valid = false;
        foreach ($browsers as $brand => $version) {
            if (check_browser_version($brand, $version)) {
                $valid = true;
            }
        }

        if (!$valid) {
            return false;
        }
    }

    $ie = check_browser_version('MSIE', 6.0);
    $ff = check_browser_version('Gecko', 20051106);
    $op = check_browser_version('Opera', 9.0);
    $sa = check_browser_version('Safari', 412);

    if (!$ie && !$ff && !$op && !$sa) {
        /** @see http://en.wikipedia.org/wiki/User_agent */
        // Gecko build 20051107 is what is in Firefox 1.5.
        // We still have issues with AJAX in other browsers.
        return false;
    }

    if (!empty($CFG->enableajax) && (!empty($USER->ajax) || !isloggedin())) {
        return true;
    } else {
        return false;
    }
}


/**
 * Used to create view of document to be passed to JavaScript on pageload.
 * We use this class to pass data from PHP to JavaScript.
 */
class jsportal {

    var $currentblocksection = null;
    var $blocks = array();


    /**
     * Takes id of block and adds it
     */
    function block_add($id, $hidden=false){
        $hidden_binary = 0;

        if ($hidden) {
            $hidden_binary = 1;
        }
        $this->blocks[count($this->blocks)] = array($this->currentblocksection, $id, $hidden_binary);
    }


    /**
     * Prints the JavaScript code needed to set up AJAX for the course.
     */
    function print_javascript($courseid, $return=false) {
        global $CFG, $USER;

        $blocksoutput = $output = '';
        for ($i=0; $i<count($this->blocks); $i++) {
            $blocksoutput .= "['".$this->blocks[$i][0]."',
                             '".$this->blocks[$i][1]."',
                             '".$this->blocks[$i][2]."']";

            if ($i != (count($this->blocks) - 1)) {
                $blocksoutput .= ',';
            }
        }
        $output .= "<script type=\"text/javascript\">\n";
        $output .= "    main.portal.id = ".$courseid.";\n";
        $output .= "    main.portal.blocks = new Array(".$blocksoutput.");\n";
        $output .= "    main.portal.strings['wwwroot']='".$CFG->wwwroot."';\n";
        $output .= "    main.portal.strings['pixpath']='".$CFG->pixpath."';\n";
        $output .= "    main.portal.strings['marker']='".get_string('markthistopic', '', '_var_')."';\n";
        $output .= "    main.portal.strings['marked']='".get_string('markedthistopic', '', '_var_')."';\n";
        $output .= "    main.portal.strings['hide']='".get_string('hide')."';\n";
        $output .= "    main.portal.strings['hidesection']='".get_string('hidesection', '', '_var_')."';\n";
        $output .= "    main.portal.strings['show']='".get_string('show')."';\n";
        $output .= "    main.portal.strings['delete']='".get_string('delete')."';\n";
        $output .= "    main.portal.strings['move']='".get_string('move')."';\n";
        $output .= "    main.portal.strings['movesection']='".get_string('movesection', '', '_var_')."';\n";
        $output .= "    main.portal.strings['moveleft']='".get_string('moveleft')."';\n";
        $output .= "    main.portal.strings['moveright']='".get_string('moveright')."';\n";
        $output .= "    main.portal.strings['update']='".get_string('update')."';\n";
        $output .= "    main.portal.strings['groupsnone']='".get_string('groupsnone')."';\n";
        $output .= "    main.portal.strings['groupsseparate']='".get_string('groupsseparate')."';\n";
        $output .= "    main.portal.strings['groupsvisible']='".get_string('groupsvisible')."';\n";
        $output .= "    main.portal.strings['clicktochange']='".get_string('clicktochange')."';\n";
        $output .= "    main.portal.strings['deletecheck']='".get_string('deletecheck','','_var_')."';\n";
        $output .= "    main.portal.strings['resource']='".get_string('resource')."';\n";
        $output .= "    main.portal.strings['activity']='".get_string('activity')."';\n";
        $output .= "    main.portal.strings['sesskey']='".sesskey()."';\n";
        $output .= "    onloadobj.load();\n";
        $output .= "    main.process_blocks();\n";
        $output .= "</script>";
        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

}

?>
