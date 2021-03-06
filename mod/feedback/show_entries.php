<?php // $Id$
/**
* print the single entries
*
* @version $Id$
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once("lib.php");
    
    ////////////////////////////////////////////////////////
    //get the params
    ////////////////////////////////////////////////////////
    $id = required_param('id', PARAM_INT);
    $userid = optional_param('userid', false, PARAM_INT);
    $lstgroupid = optional_param('lstgroupid', -2, PARAM_INT); //groupid (choosen from dropdownlist)
    $do_show = required_param('do_show', PARAM_ALPHA);
    // $SESSION->feedback->current_tab = $do_show;
    $current_tab = $do_show;

    //check, whether a group is selected
    if($lstgroupid == -1) {
        $SESSION->feedback->lstgroupid = false;
    }else {
        if((!isset($SESSION->feedback->lstgroupid)) || $lstgroupid != -2)
            $SESSION->feedback->lstgroupid = $lstgroupid;
    }
    
    ////////////////////////////////////////////////////////
    //get the objects
    ////////////////////////////////////////////////////////
    
    if($userid) {
        $formdata->userid = intval($userid);
    }

    if ($id) {
        if (! $cm = get_coursemodule_from_id('feedback', $id)) {
            print_error('invalidcoursemodule');
        }
     
        if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
            print_error('coursemisconf');
        }
     
        if (! $feedback = $DB->get_record("feedback", array("id"=>$cm->instance))) {
            print_error('invalidcoursemodule');
        }
    }
    
    if(isset($SESSION->feedback->lstgroupid)) {
        if($tmpgroup = groups_get_group($SESSION->feedback->lstgroupid)) {
            if($tmpgroup->courseid != $course->id) {
                $SESSION->feedback->lstgroupid = false;
            }
        }else {
            $SESSION->feedback->lstgroupid = false;
        }
    }
    $capabilities = feedback_load_capabilities($cm->id);

    require_login($course->id, true, $cm);
    
    if(($formdata = data_submitted()) AND !confirm_sesskey()) {
        print_error('invalidsesskey');
    }
    
    if(!$capabilities->viewreports){
        print_error('error');
    }

    ////////////////////////////////////////////////////////
    //get the responses of given user
    ////////////////////////////////////////////////////////
    if($do_show == 'showoneentry') {
        //get the feedbackitems
        $feedbackitems = $DB->get_records('feedback_item', array('feedback'=>$feedback->id), 'position');
        $feedbackcompleted = $DB->get_record('feedback_completed', array('feedback'=>$feedback->id, 'userid'=>$formdata->userid, 'anonymous_response'=>FEEDBACK_ANONYMOUS_NO)); //arb
    }
    
    /// Print the page header
    $strfeedbacks = get_string("modulenameplural", "feedback");
    $strfeedback  = get_string("modulename", "feedback");
    $buttontext = update_module_button($cm->id, $course->id, $strfeedback);
    
    $navlinks = array();
    $navlinks[] = array('name' => $strfeedbacks, 'link' => "index.php?id=$course->id", 'type' => 'activity');
    $navlinks[] = array('name' => format_string($feedback->name), 'link' => "", 'type' => 'activityinstance');
    
    $navigation = build_navigation($navlinks);
    
    print_header_simple(format_string($feedback->name), "",
                 $navigation, "", "", true, $buttontext, navmenu($course, $cm));
                 
    include('tabs.php');

    /// Print the main part of the page
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////
    /// Print the links to get responses and analysis
    ////////////////////////////////////////////////////////
    if($do_show == 'showentries'){
        //print the link to analysis
        if($capabilities->viewreports) {
            //get the effective groupmode of this course and module
            $groupmode = groupmode($course, $cm);
            
            //get students in conjunction with groupmode
            if($groupmode > 0) {
                if($SESSION->feedback->lstgroupid == -2) {
                    if(has_capability('moodle/site:doanything', get_context_instance(CONTEXT_SYSTEM))) {
                        $mygroupid = false;
                        $SESSION->feedback->lstgroupid = false;
                    }else{
                        if($mygroupid = mygroupid($course->id)) {
                            $mygroupid = $mygroupid[0]; //get the first groupid
                        }
                    }
                }else {
                    $mygroupid = $SESSION->feedback->lstgroupid;
                }
                if($mygroupid) {
                    $students = feedback_get_complete_users($cm->id, $mygroupid);
                } else {
                    $students = feedback_get_complete_users($cm->id);
                }
            }else {
                $students = feedback_get_complete_users($cm->id);
            }

            $mygroupid=isset($mygroupid)?$mygroupid:NULL;

            $completedFeedbackCount = feedback_get_completeds_group_count($feedback, $mygroupid);
            if($feedback->course == SITEID){
                echo '<div class="mdl-align"><a href="'.htmlspecialchars('analysis_course.php?id=' . $id . '&courseid='.$courseid).'">';
                echo get_string('course') .' '. get_string('analysis', 'feedback') . ' ('.get_string('completed_feedbacks', 'feedback').': '.intval($completedFeedbackCount).')</a>';
                helpbutton('viewcompleted', '', 'feedback', true, true);
                echo '</div>';
            }else {
                echo '<div class="mdl-align"><a href="'.htmlspecialchars('analysis.php?id=' . $id . '&courseid='.$courseid).'">';
                echo get_string('analysis', 'feedback') . ' ('.get_string('completed_feedbacks', 'feedback').': '.intval($completedFeedbackCount).')</a>';
                echo '</div>';
            }
        }
    
        //####### viewreports-start
        if($capabilities->viewreports) {
            //print the list of students
            // print_simple_box_start('center', '80%');
            print_box_start('generalbox boxaligncenter boxwidthwide');

            //available group modes (NOGROUPS, SEPARATEGROUPS or VISIBLEGROUPS)
            $feedbackgroups = groups_get_all_groups($course->id);
            //if(is_array($feedbackgroups) && $groupmode != SEPARATEGROUPS){
            if(is_array($feedbackgroups) && $groupmode > 0){
                require_once('choose_group_form.php');
                //the use_template-form
                $choose_group_form = new feedback_choose_group_form();
                $choose_group_form->set_feedbackdata(array('groups'=>$feedbackgroups, 'mygroupid'=>$mygroupid));
                $choose_group_form->set_form_elements();
                $choose_group_form->set_data(array('id'=>$id, 'lstgroupid'=>$SESSION->feedback->lstgroupid, 'do_show'=>$do_show));
                $choose_group_form->display();
            }
            echo '<div class="mdl-align"><table><tr><td width="400">';
            if (!$students) {
                if($courseid != SITEID){
                    notify(get_string('noexistingstudents'));
                }
            } else{
                echo print_string('non_anonymous_entries', 'feedback');
                echo ' ('.$DB->count_records('feedback_completed', array('feedback'=>$feedback->id, 'anonymous_response'=>FEEDBACK_ANONYMOUS_NO)).')<hr />';

                foreach ($students as $student){
                    $completedCount = $DB->count_records('feedback_completed', array('userid'=>$student->id, 'feedback'=>$feedback->id, 'anonymous_response'=>FEEDBACK_ANONYMOUS_NO));
                    if($completedCount > 0) {
                     // Are we assuming that there is only one response per user? Should westep through a feedbackcompleteds? I added the addition anonymous check to the select so that only non-anonymous submissions are retrieved. 
                        $feedbackcompleted = $DB->get_record('feedback_completed', array('feedback'=>$feedback->id, ' userid'=>$student->id, 'anonymous_response'=>FEEDBACK_ANONYMOUS_NO));
                    ?>
                        <table width="100%">
                            <tr>
                                <td align="left">
                                    <?php echo print_user_picture($student->id, $course->id, $student->picture, false, true);?>
                                </td>
                                <td align="left">
                                    <?php echo fullname($student);?>
                                </td>
                                <td align="right">
                                <?php
                                    $show_button_link = $ME;
                                    $show_button_options = array('sesskey'=>sesskey(), 'userid'=>$student->id, 'do_show'=>'showoneentry', 'id'=>$id);
                                    $show_button_label = get_string('show_entries', 'feedback');
                                    print_single_button($show_button_link, $show_button_options, $show_button_label, 'post');
                                ?>
                                </td>
                    <?php
                        if($capabilities->deletesubmissions) {
                    ?>
                                <td align="right">
                                <?php
                                    $delete_button_link = 'delete_completed.php';
                                    $delete_button_options = array('sesskey'=>sesskey(), 'completedid'=>$feedbackcompleted->id, 'do_show'=>'showoneentry', 'id'=>$id);
                                    $delete_button_label = get_string('delete_entry', 'feedback');
                                    print_single_button($delete_button_link, $delete_button_options, $delete_button_label, 'post');
                                ?>
                                </td>
                    <?php
                        }
                    ?>
                            </tr>
                        </table>
                    <?php
                    }
                }
            }
    ?>
            <hr />
            <table width="100%">
                <tr>
                    <td align="left" colspan="2">
                        <?php print_string('anonymous_entries', 'feedback');?>&nbsp;(<?php echo $DB->count_records('feedback_completed', array('feedback'=>$feedback->id, 'anonymous_response'=>FEEDBACK_ANONYMOUS_YES));?>)
                    </td>
                    <td align="right">
                        <?php
                            $show_anon_button_link = 'show_entries_anonym.php';
                            $show_anon_button_options = array('sesskey'=>sesskey(), 'userid'=>0, 'do_show'=>'showoneentry', 'id'=>$id);
                            $show_anon_button_label = get_string('show_entries', 'feedback');
                            print_single_button($show_anon_button_link, $show_anon_button_options, $show_anon_button_label, 'post');
                        ?>
                    </td>
                </tr>
            </table> 
    <?php
            echo '</td></tr></table></div>';
            // print_simple_box_end();
            print_box_end();
        }
    
    }
    ////////////////////////////////////////////////////////
    /// Print the responses of the given user
    ////////////////////////////////////////////////////////
    if($do_show == 'showoneentry') {
        print_heading(format_text($feedback->name));
        
        //print the items
        if(is_array($feedbackitems)){
            $usr = $DB->get_record('user', array('id'=>$formdata->userid));
            if($feedbackcompleted) {
                echo '<p align="center">'.UserDate($feedbackcompleted->timemodified).'<br />('.fullname($usr).')</p>';
            } else {
                echo '<p align="center">'.get_string('not_completed_yet','feedback').'</p>';
            }
            // print_simple_box_start("center", '50%');
            print_box_start('generalbox boxaligncenter boxwidthnormal');
            echo '<form>';
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            echo '<table width="100%">';
            $itemnr = 0;
            foreach($feedbackitems as $feedbackitem){
                //get the values
                $value = $DB->get_record('feedback_value', array('completed'=>$feedbackcompleted->id, 'item'=>$feedbackitem->id));
                echo '<tr>';
                if($feedbackitem->hasvalue == 1 AND $feedback->autonumbering) {
                    $itemnr++;
                    echo '<td valign="top">' . $itemnr . '.&nbsp;</td>';
                } else {
                    echo '<td>&nbsp;</td>';
                }
                
                if($feedbackitem->typ != 'pagebreak') {
                    if(isset($value->value)) {
                        feedback_print_item($feedbackitem, $value->value, true);
                    }else {
                        feedback_print_item($feedbackitem, false, true);
                    }
                }else {
                    echo '<td><hr /></td>';
                }
                echo '</tr>';
            }
            echo '<tr><td colspan="2" align="center">';
            echo '</td></tr>';
            echo '</table>';
            echo '</form>';
            // print_simple_box_end();
            print_box_end();
        }
        print_continue(htmlspecialchars('show_entries.php?id='.$id.'&do_show=showentries'));
    }
    /// Finish the page
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    print_footer($course);

?>
