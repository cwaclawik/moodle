<?php // $Id$

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com     //
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
 * Processes actions from the admin_setting_managefilters object (defined in
 * adminlib.php).
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package administration
 *//** */

    require_once(dirname(__FILE__) . '/../config.php');
    require_once($CFG->libdir . '/adminlib.php');

    $action = optional_param('action', '', PARAM_ACTION);
    $filterpath = optional_param('filterpath', '', PARAM_PATH);

    require_login();
    $systemcontext = get_context_instance(CONTEXT_SYSTEM);
    require_capability('moodle/site:config', $systemcontext);

    $returnurl = "$CFG->wwwroot/$CFG->admin/filters.php";
    admin_externalpage_setup('managefilters');

    $filters = filter_get_global_states();

    // In case any new filters have been installed, but not put in the table yet.
    $fitlernames = filter_get_all_installed();
    $newfilters = $fitlernames;
    foreach ($filters as $filter => $notused) {
        unset($newfilters[$filter]);
    }

/// Process actions ============================================================

    if ($action) {
        if (!isset($filters[$filterpath]) && !isset($newfilters[$filterpath])) {
            throw new moodle_exception('filternotinstalled', 'error', $returnurl, $filterpath);
        }

        if (!confirm_sesskey()) {
            redirect($returnurl);
        }
    }

    switch ($action) {

    case 'setstate':
        if ($newstate = optional_param('newstate', '', PARAM_INTEGER)) {
            filter_set_global_state($filterpath, $newstate);
            if ($newstate == TEXTFILTER_DISABLED) {
                filter_set_applies_to_strings($filterpath, false);
            }
            unset($newfilters[$filterpath]);
        }
        break;

    case 'setapplyto':
        $applytostrings = optional_param('stringstoo', false, PARAM_BOOL);
        filter_set_applies_to_strings($filterpath, $applytostrings);
        break;

    case 'down':
        if (isset($filters[$filterpath])) {
            $oldpos = $filters[$filterpath]->sortorder;
            if ($oldpos <= count($filters)) {
                filter_set_global_state($filterpath, $filters[$filterpath]->active, $oldpos + 1);
            }
        }
        break;

    case 'up':
        if (isset($filters[$filterpath])) {
            $oldpos = $filters[$filterpath]->sortorder;
            if ($oldpos >= 1) {
                filter_set_global_state($filterpath, $filters[$filterpath]->active, $oldpos - 1);
            }
        }
        break;

    case 'delete':
        if (!empty($filternames[$filterpath])) {
            $filtername = $filternames[$filterpath];
        } else {
            $filtername = $filterpath;
        }

        if (substr($filterpath, 0, 4) == 'mod/') {
            $mod = basename($filterpath);
            $a = new stdClass;
            $a->filter = $filtername;
            $a->module = get_string('modulename', $mod);
            print_error('cannotdeletemodfilter', 'admin', $returnurl, $a);
        }

        // If not yet confirmed, display a confirmation message.
        if (!optional_param('confirm', '', PARAM_BOOL)) {
            $title = get_string('deletefilterareyousure', 'admin', $filtername);
            admin_externalpage_print_header();
            print_heading($title);
            notice_yesno(get_string('deletefilterareyousuremessage', 'admin', $filtername), $returnurl .
                    '?action=delete&amp;filterpath=' . $filterpath . '&amp;confirm=1&amp;sesskey=' . sesskey(),
                    $returnurl, NULL, NULL, 'post', 'get');
            admin_externalpage_print_footer();
            exit;
        }

        // Do the deletion.
        $title = get_string('deletingfilter', 'admin', $filtername);
        admin_externalpage_print_header();
        print_heading($title);

        // Delete all data for this plugin.
        filter_delete_all_for_filter($filterpath);

        $a = new stdClass;
        $a->filter = $filtername;
        $a->directory = $filterpath;
        print_box(get_string('deletefilterfiles', 'admin', $a), 'generalbox', 'notice');
        print_continue($returnurl);
        admin_externalpage_print_footer();
        exit;
    }

    // Add any missing filters to the DB table.
    foreach ($newfilters as $filter => $notused) {
        filter_set_global_state($filter, TEXTFILTER_DISABLED);
    }

    // Reset caches and return
    if ($action) {
        reset_text_filters_cache();
        redirect($returnurl);
    }

/// End of process actions =====================================================

/// Print the page heading.
    admin_externalpage_print_header();
    print_heading(get_string('manageqtypes', 'admin'));

    $activechoices = array(
        TEXTFILTER_DISABLED => get_string('disabled', 'filters'),
        TEXTFILTER_OFF => get_string('offbutavailable', 'filters'),
        TEXTFILTER_ON => get_string('on', 'filters'),
    );
    $applytochoices = array(
        0 => get_string('content', 'filters'),
        1 => get_string('contentandheadings', 'filters'),
    );

    $filters = filter_get_global_states();

    // In case any new filters have been installed, but not put in the table yet.
    $filternames = filter_get_all_installed();
    $newfilters = $filternames;
    foreach ($filters as $filter => $notused) {
        unset($newfilters[$filter]);
    }
    $stringfilters = filter_get_string_filters();

    $table = new object();
    $table->head  = array(get_string('filter'), get_string('isactive', 'filters'),
            get_string('order'), get_string('applyto', 'filters'), get_string('settings'), get_string('delete'));
    $table->align = array('left', 'left', 'center', 'left', 'left');
    $table->width = '100%';
    $table->data  = array();

    $lastactive = null;
    foreach ($filters as $filter => $filterinfo) {
        if ($filterinfo->active != TEXTFILTER_DISABLED) {
            $lastactive = $filter;
        }
    }

    // iterate through filters adding to display table
    $firstrow = true;
    foreach ($filters as $filter => $filterinfo) {
        $applytostrings = isset($stringfilters[$filter]) && $filterinfo->active != TEXTFILTER_DISABLED;
        $row = get_table_row($filterinfo, $firstrow, $filter == $lastactive, $applytostrings);
        $table->data[] = $row;
        if ($filterinfo->active == TEXTFILTER_DISABLED) {
            $table->rowclass[] = 'dimmed_text';
        } else {
            $table->rowclass[] = '';
        }
        $firstrow = false;
    }
    foreach ($newfilters as $filter => $filtername) {
        $filterinfo = new stdClass;
        $filterinfo->filter = $filter;
        $filterinfo->active = TEXTFILTER_DISABLED;
        $row = get_table_row($filterinfo, false, false, false);
        $table->data[] = $row;
        $table->rowclass[] = 'dimmed_text';
    }

    print_table($table);
    echo '<p class="filtersettingnote">' . get_string('filterallwarning', 'filters') . '</p>';
    admin_externalpage_print_footer();

/// Display helper functions ===================================================

function action_url($filterpath, $action) {
    global $returnurl;
    return $returnurl . '?sesskey=' . sesskey() . '&amp;filterpath=' .
            urlencode($filterpath) . '&amp;action=' . $action;
}

function action_icon($url, $icon, $straction) {
    global $CFG;
    return '<a href="' . $url . '" title="' . $straction . '">' .
            '<img src="' . $CFG->pixpath . '/t/' . $icon . '.gif" alt="' . $straction . '" /></a> ';
}

function get_table_row($filterinfo, $isfirstrow, $islastactive, $applytostrings) {
    global $CFG, $activechoices, $applytochoices, $filternames;
    $row = array();
    $filter = $filterinfo->filter;

    // Filter name
    if (!empty($filternames[$filter])) {
        $row[] = $filternames[$filter];
    } else {
        $row[] = '<span class="error">' . get_string('filemissing', '', $filter) . '</span>';
    }

    // Disable/off/on
    $row[] = popup_form(action_url($filter, 'setstate') . '&amp;newstate=', $activechoices,
            'active' . basename($filter), $filterinfo->active, '', '', '', true, 'self', '', NULL, get_string('save', 'admin'));

    // Re-order
    $updown = '';
    $spacer = '<img src="' . $CFG->pixpath . '/spacer.gif" class="iconsmall" alt="" /> ';
    if ($filterinfo->active != TEXTFILTER_DISABLED) {
        if (!$isfirstrow) {
            $updown .= action_icon(action_url($filter, 'up'), 'up', get_string('up'));
        } else {
            $updown .= $spacer;
        }
        if (!$islastactive) {
            $updown .= action_icon(action_url($filter, 'down'), 'down', get_string('down'));
        } else {
            $updown .= $spacer;
        }
    }
    $row[] = $updown;

    // Apply to strings.
    $row[] = popup_form(action_url($filter, 'setapplyto') . '&amp;stringstoo=', $applytochoices,
            'applyto' . basename($filter), $applytostrings, '', '', '', true, 'self', '', NULL, get_string('save', 'admin'),
            $filterinfo->active == TEXTFILTER_DISABLED);

    // Settings link, if required
    if (filter_has_global_settings($filter)) {
        $row[] = '<a href="' . $CFG->wwwroot . '/' . $CFG->admin . '/settings.php?section=filtersetting' .
                str_replace('/', '',$filter) . '">' . get_string('settings') . '</a>';
    } else {
        $row[] = '';
    }

    // Delete
    if (substr($filter, 0, 4) != 'mod/') {
        $row[] = '<a href="' . action_url($filter, 'delete') . '">' . get_string('delete') . '</a>';
    } else {
        $row[] = '';
    }

    return $row;
}
