<?php  // $Id$
/**
 * For a given question type, list the number of 
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package roles
 */

/** */
require_once(dirname(__FILE__).'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/questionlib.php');

// Get URL parameters.
$requestedqtype = optional_param('qtype', '', PARAM_SAFEDIR);

// Print the header & check permissions.
admin_externalpage_setup('reportquestioninstances');
admin_externalpage_print_header();

// Log.
add_to_log(SITEID, "admin", "report questioninstances", "report/questioninstances/index.php?qtype=$requestedqtype", $requestedqtype);

// Prepare the list of capabilites to choose from
$qtypechoices = array();
foreach ($QTYPES as $qtype) {
    $qtypechoices[$qtype->name()] = $qtype->local_name();
}

// Print the settings form.
print_box_start('generalbox boxwidthwide boxaligncenter centerpara');
echo '<form method="get" action="." id="settingsform"><div>';
print_heading(get_string('reportsettings', 'report_questioninstances'));
echo '<p id="intro">', get_string('intro', 'report_questioninstances') , '</p>';
echo '<p><label for="menuqtype"> ' . get_string('questiontype', 'admin') . '</label> ';
choose_from_menu($qtypechoices, 'qtype', $requestedqtype, get_string('all'), '', '_all_');
echo '</p>';
echo '<p><input type="submit" id="settingssubmit" value="' .
        get_string('getreport', 'report_questioninstances') . '" /></p>';
echo '</div></form>';
print_box_end();

// If we have a qtype to report on, generate the report.
if ($requestedqtype) {

    // Work out the bits needed for the SQL WHERE clauses.
    if ($requestedqtype == 'missingtype') {
        $othertypes = array_keys($QTYPES);
        $key = array_search('missingtype', $othertypes);
        unset($othertypes[$key]);
        list($sqlqtypetest, $params) = $DB->get_in_or_equal($othertypes, SQL_PARAMS_QM, '', false);
        $sqlqtypetest = 'WHERE qtype ' . $sqlqtypetest;
        $title = get_string('reportformissingqtypes', 'report_questioninstances');
    } else if ($requestedqtype == '_all_') {
        $sqlqtypetest = '';
        $params = array();
        $title = get_string('reportforallqtypes', 'report_questioninstances');
    } else {
        $sqlqtypetest = 'WHERE qtype = ?';
        $params = array($requestedqtype);
        $title = get_string('reportforqtype', 'report_questioninstances', $QTYPES[$requestedqtype]->local_name());
    }

    // Get the question counts, and all the context information, for each
    // context. That is, rows of these results can be used as $context objects.
    $counts = $DB->get_records_sql("
            SELECT qc.contextid, count(1) as numquestions, sum(hidden) as numhidden, con.id, con.contextlevel, con.instanceid, con.path, con.depth 
            FROM {question} q
            JOIN {question_categories} qc ON q.category = qc.id
            JOIN {context} con ON con.id = qc.contextid
            $sqlqtypetest
            GROUP BY contextid, con.id, con.contextlevel, con.instanceid, con.path, con.depth
            ORDER BY numquestions DESC, numhidden ASC, con.contextlevel ASC, con.id ASC", $params);

    // Print the report heading.
    print_heading($title);

    // Initialise the table.
    $table = new stdClass;
    $table->head = array(
            get_string('context', 'role'),
            get_string('totalquestions', 'report_questioninstances'),
            get_string('visiblequestions', 'report_questioninstances'),
            get_string('hiddenquestions', 'report_questioninstances'));
    $table->data = array();
    $table->class = '';
    $table->id = '';

    // Add the data for each row.
    $totalquestions = 0;
    $totalvisible = 0;
    $totalhidden = 0;
    foreach ($counts as $count) {
        // Work out a link for editing questions in this context.
        $contextname = print_context_name($count);
        $url = question_edit_url($count);
        if ($url) {
            $contextname = '<a href="' . $url . '" title="' .
                    get_string('editquestionshere', 'report_questioninstances') .
                    '">' . $contextname . '</a>';
        }

        // Put the scores in the table.
        $numvisible = $count->numquestions - $count->numhidden;
        $table->data[] = array(
                $contextname,
                $count->numquestions,
                $numvisible,
                $count->numhidden);

        // Update the totals.
        $totalquestions += $count->numquestions;
        $totalvisible += $numvisible;
        $totalhidden += $count->numhidden;
    }

    // Add a totals row.
    $table->data[] = array(
            '<b>' . get_string('total') . '</b>',
            $totalquestions,
            $totalvisible,
            $totalhidden);

    // Print it.
    print_table($table);
}

// Footer.
admin_externalpage_print_footer();
?>
