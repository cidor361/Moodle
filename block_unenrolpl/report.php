<?php
/**
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once(__DIR__.'/../../config.php');
require_once("{$CFG->libdir}/completionlib.php");
//require_once('../../config.php');
require_once('unenrolpl_form.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->libdir.'/tablelib.php');

/**
 * Configuration
 */
define('COMPLETION_REPORT_PAGE',        25);
define('COMPLETION_REPORT_COL_TITLES',  true);

global $COURSE;

/*
 * Setup page, check permissions
 */

// Get course
$courseid = required_param('courseid', PARAM_INT);
$format = optional_param('format','',PARAM_ALPHA);
$sort = optional_param('sort','',PARAM_ALPHA);
$edituser = optional_param('edituser', 0, PARAM_INT);


$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

// Paging
$start   = optional_param('start', 0, PARAM_INT);
$sifirst = optional_param('sifirst', 'all', PARAM_NOTAGS);
$silast  = optional_param('silast', 'all', PARAM_NOTAGS);

// Whether to show extra user identity information
$extrafields = get_extra_user_fields($context);
$leftcols = 1 + count($extrafields);

// Check permissions
require_login($course);

require_capability('report/completion:view', $context);

// Get group mode
$group = groups_get_course_group($course, true); // Supposed to verify group
if ($group === 0 && $course->groupmode == SEPARATEGROUPS) {
    require_capability('moodle/site:accessallgroups',$context);
}

/**
 * Load data
 */

// Retrieve course_module data for all modules in the course
$modinfo = get_fast_modinfo($course);

// Get criteria for course
$completion = new completion_info($course);

if (!$completion->has_criteria()) {
    print_error('nocriteriaset', 'completion', $CFG->wwwroot.'/course/report.php?id='.$course->id);
}

// Get criteria and put in correct order
$criteria = array();

foreach ($completion->get_criteria(COMPLETION_CRITERIA_TYPE_COURSE) as $criterion) {
    $criteria[] = $criterion;
}

foreach ($completion->get_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY) as $criterion) {
    $criteria[] = $criterion;
}

foreach ($completion->get_criteria() as $criterion) {
    if (!in_array($criterion->criteriatype, array(
        COMPLETION_CRITERIA_TYPE_COURSE, COMPLETION_CRITERIA_TYPE_ACTIVITY))) {
        $criteria[] = $criterion;
    }
}

// Can logged in user mark users as complete?
// (if the logged in user has a role defined in the role criteria)
$allow_marking = false;
$allow_marking_criteria = null;

if (!$csv) {
    // Get role criteria
    $rcriteria = $completion->get_criteria(COMPLETION_CRITERIA_TYPE_ROLE);

    if (!empty($rcriteria)) {

        foreach ($rcriteria as $rcriterion) {
            $users = get_role_users($rcriterion->role, $context, true);

            // If logged in user has this role, allow marking complete
            if ($users && in_array($USER->id, array_keys($users))) {
                $allow_marking = true;
                $allow_marking_criteria = $rcriterion->id;
                break;
            }
        }
    }
}

/*
 * Setup page header
 */
if ($csv) {

    $shortname = format_string($course->shortname, true, array('context' => $context));
    $shortname = preg_replace('/[^a-z0-9-]/', '_',core_text::strtolower(strip_tags($shortname)));

    $export = new csv_export_writer();
    $export->set_filename('completion-'.$shortname);

} else {
    // Navigation and header
    $strcompletion = get_string('coursecompletion');

    $PAGE->set_title($strcompletion);
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();

    // Handle groups (if enabled)
    groups_print_course_menu($course, $CFG->wwwroot.'/report/completion/index.php?course='.$course->id);
}

if ($sifirst !== 'all') {
    set_user_preference('ifirst', $sifirst);
}
if ($silast !== 'all') {
    set_user_preference('ilast', $silast);
}

if (!empty($USER->preference['ifirst'])) {
    $sifirst = $USER->preference['ifirst'];
} else {
    $sifirst = 'all';
}

if (!empty($USER->preference['ilast'])) {
    $silast = $USER->preference['ilast'];
} else {
    $silast = 'all';
}

// Generate where clause
$where = array();
$where_params = array();

if ($sifirst !== 'all') {
    $where[] = $DB->sql_like('u.firstname', ':sifirst', false);
    $where_params['sifirst'] = $sifirst.'%';
}

if ($silast !== 'all') {
    $where[] = $DB->sql_like('u.lastname', ':silast', false);
    $where_params['silast'] = $silast.'%';
}

// Get user match count
$total = $completion->get_num_tracked_users(implode(' AND ', $where), $where_params, $group);

// Total user count
$grandtotal = $completion->get_num_tracked_users('', array(), $group);

// If no users in this course what-so-ever
if (!$grandtotal) {
    echo $OUTPUT->container(get_string('err_nousers', 'completion'), 'errorbox errorboxcontent');
    echo $OUTPUT->footer();
    exit;
}

// Get user data
$progress = array();

if ($total) {
    $progress = $completion->get_progress_all(
        implode(' AND ', $where),
        $where_params,
        $group,
        $firstnamesort ? 'u.firstname ASC' : 'u.lastname ASC',
        $csv ? 0 : COMPLETION_REPORT_PAGE,
        $csv ? 0 : $start,
        $context
    );
}

/*
 * Draw table header
 */

// Start of table
if (!$csv) {
    print '<br class="clearer"/>'; // ugh

    $total_header = ($total == $grandtotal) ? $total : "{$total}/{$grandtotal}";
    echo $OUTPUT->heading(get_string('listofusers', 'block_unenrolpl').": {$total_header}", 3);

    print $pagingbar;

    if (!$total) {
        echo $OUTPUT->heading(get_string('nothingtodisplay'), 2);
        echo $OUTPUT->footer();
        exit;
    }

    print '<table id="completion-progress" class="table table-bordered generaltable flexible boxaligncenter
        completionreport" style="text-align: left" cellpadding="5" border="1">';

    // Print user heading and icons
    print '<tr>';

    // User heading / sort option
    print '<th scope="col" class="completion-sortchoice" style="clear: both;">';

    $sistring = "&amp;silast={$silast}&amp;sifirst={$sifirst}";

    print get_string('firstname', block_unenrolpl);
    print ' / ';
    print get_string('lastname');
    print '</th>';

    // Print user identity columns
    foreach ($extrafields as $field) {
        echo '<th scope="col" class="completion-identifyfield">' .
            get_user_field_name($field) . '</th>';
    }

    ///
    /// Print criteria icons
    ///
    foreach ($criteria as $criterion) {

        // Generate icon details
        $iconlink = '';
        $iconalt = ''; // Required
        $iconattributes = array('class' => 'icon');
        switch ($criterion->criteriatype) {

            case COMPLETION_CRITERIA_TYPE_ACTIVITY:

                // Display icon
                $iconlink = $CFG->wwwroot.'/mod/'.$criterion->module.'/view.php?id='.$criterion->moduleinstance;
                $iconattributes['title'] = $modinfo->cms[$criterion->moduleinstance]->get_formatted_name();
                $iconalt = get_string('modulename', $criterion->module);
                break;

            case COMPLETION_CRITERIA_TYPE_COURSE:
                // Load course
                $crs = $DB->get_record('course', array('id' => $criterion->courseinstance));

                // Display icon
                $iconlink = $CFG->wwwroot.'/course/view.php?id='.$criterion->courseinstance;
                $iconattributes['title'] = format_string($crs->fullname, true, array('context' => context_course::instance($crs->id, MUST_EXIST)));
                $iconalt = format_string($crs->shortname, true, array('context' => context_course::instance($crs->id)));
                break;

            case COMPLETION_CRITERIA_TYPE_ROLE:
                // Load role
                $role = $DB->get_record('role', array('id' => $criterion->role));

                // Display icon
                $iconalt = $role->name;
                break;
        }

        // Create icon alt if not supplied
        if (!$iconalt) {
            $iconalt = $criterion->get_title();
        }

        // Print icon and cell
        print '<th class="criteriaicon">';

        print ($iconlink ? '<a href="'.$iconlink.'" title="'.$iconattributes['title'].'">' : '');
        print $OUTPUT->render($criterion->get_icon($iconalt, $iconattributes));
        print ($iconlink ? '</a>' : '');

        print '</th>';
    }

    // Overall course completion status
    print '<th class="criteriaicon">';
    print $OUTPUT->pix_icon('i/course', get_string('coursecomplete', 'completion'));
    print '</th>';

    print '</tr></thead>';

    echo '<tbody>';
} else {
    // The CSV headers
    $row = array();

    $row[] = get_string('id', 'report_completion');
    $row[] = get_string('name', 'report_completion');
    foreach ($extrafields as $field) {
        $row[] = get_user_field_name($field);
    }

    // Add activity headers
    foreach ($criteria as $criterion) {

        // Handle activity completion differently
        if ($criterion->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {

            // Load activity
            $mod = $criterion->get_mod_instance();
            $row[] = $formattedname = format_string($mod->name, true,
                array('context' => context_module::instance($criterion->moduleinstance)));
            $row[] = $formattedname . ' - ' . get_string('completiondate', 'report_completion');
        }
        else {
            // Handle all other criteria
            $row[] = strip_tags($criterion->get_title_detailed());
        }
    }

    $row[] = get_string('coursecomplete', 'completion');

    $export->add_data($row);
}

///
/// Display a row for each user
///
foreach ($progress as $user) {

    // User name
    if ($csv) {
        $row = array();
        $row[] = $user->id;
        $row[] = fullname($user);
        foreach ($extrafields as $field) {
            $row[] = $user->{$field};
        }
    } else {
        print PHP_EOL.'<tr id="user-'.$user->id.'">';

        if (completion_can_view_data($user->id, $course)) {
            $userurl = new moodle_url('/blocks/completionstatus/details.php', array('course' => $course->id, 'user' => $user->id));
        } else {
            $userurl = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $course->id));
        }

        print '<th scope="row"><a href="'.$userurl->out().'">'.fullname($user).'</a></th>';
        foreach ($extrafields as $field) {
            echo '<td>'.s($user->{$field}).'</td>';
        }
    }

    $qq = 1;
    $arrayofusers = array();
    // Progress for each course completion criteria
    foreach ($criteria as $criterion) {

        $criteria_completion = $completion->get_user_completion($user->id, $criterion);
        $is_complete = $criteria_completion->is_complete();

        // Handle activity completion differently
        if ($criterion->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {

            // Load activity
            $activity = $modinfo->cms[$criterion->moduleinstance];

            // Get progress information and state
            if (array_key_exists($activity->id, $user->progress)) {
                $state = $user->progress[$activity->id]->completionstate;
            } else if ($is_complete) {
                $state = COMPLETION_COMPLETE;
            } else {
                $state = COMPLETION_INCOMPLETE;
            }
            if ($is_complete) {
                $date = userdate($criteria_completion->timecompleted, get_string('strftimedatetimeshort', 'langconfig'));
            } else {
                $date = '';
            }

            // Work out how it corresponds to an icon
            switch($state) {
                case COMPLETION_INCOMPLETE    : $completiontype = 'n';    break;
                case COMPLETION_COMPLETE      : $completiontype = 'y';    break;
                case COMPLETION_COMPLETE_PASS : $completiontype = 'pass'; break;
                case COMPLETION_COMPLETE_FAIL : $completiontype = 'fail'; break;
            }

            $auto = $activity->completion == COMPLETION_TRACKING_AUTOMATIC;
            $completionicon = 'completion-'.($auto ? 'auto' : 'manual').'-'.$completiontype;

            $describe = get_string('completion-'.$completiontype, 'completion');
            $a = new StdClass();
            $a->state     = $describe;
            $a->date      = $date;
            $a->user      = fullname($user);
            $a->activity  = $activity->get_formatted_name();
            $fulldescribe = get_string('progress-title', 'completion', $a);

            if ($csv) {
                $row[] = $describe;
                $row[] = $date;
            } else {
                print '<td class="completion-progresscell">';

                print $OUTPUT->pix_icon('i/' . $completionicon, $fulldescribe);

                print '</td>';
            }

            continue;
        }

        // Handle all other criteria
        $completiontype = $is_complete ? 'y' : 'n';



        //Array of users completed course
        $q = $user->id;
        if ($completiontype == 'y') {
            $arrayofusers[$qq] = $q;
            $qq = $qq + 1;
        }



        $completionicon = 'completion-auto-'.$completiontype;

        $describe = get_string('completion-'.$completiontype, 'completion');

        $a = new stdClass();
        $a->state    = $describe;

        if ($is_complete) {
            $a->date = userdate($criteria_completion->timecompleted, get_string('strftimedatetimeshort', 'langconfig'));
        } else {
            $a->date = '';
        }

        $a->user     = fullname($user);
        $a->activity = strip_tags($criterion->get_title());
        $fulldescribe = get_string('progress-title', 'completion', $a);

        if ($csv) {
            $row[] = $a->date;
        } else {

            print '<td class="completion-progresscell">';

            if ($allow_marking_criteria === $criterion->id) {
                $describe = get_string('completion-'.$completiontype, 'completion');

                $toggleurl = new moodle_url(
                    '/course/togglecompletion.php',
                    array(
                        'user' => $user->id,
                        'course' => $course->id,
                        'rolec' => $allow_marking_criteria,
                        'sesskey' => sesskey()
                    )
                );

                print '<a href="'.$toggleurl->out().'" title="'.s(get_string('clicktomarkusercomplete', 'report_completion')).'">' .
                    $OUTPUT->pix_icon('i/completion-manual-' . ($is_complete ? 'y' : 'n'), $describe) . '</a></td>';
            } else {
                print $OUTPUT->pix_icon('i/' . $completionicon, $fulldescribe) . '</td>';
            }

            print '</td>';
        }
    }

    // Handle overall course completion

    // Load course completion
    $params = array(
        'userid'    => $user->id,
        'course'    => $course->id
    );

    $ccompletion = new completion_completion($params);
    $completiontype =  $ccompletion->is_complete() ? 'y' : 'n';

    $describe = get_string('completion-'.$completiontype, 'completion');

    $a = new StdClass;

    if ($ccompletion->is_complete()) {
        $a->date = userdate($ccompletion->timecompleted, get_string('strftimedatetimeshort', 'langconfig'));
    } else {
        $a->date = '';
    }

    $a->state    = $describe;
    $a->user     = fullname($user);
    $a->activity = strip_tags(get_string('coursecomplete', 'completion'));
    $fulldescribe = get_string('progress-title', 'completion', $a);

    if ($csv) {
        $row[] = $a->date;
    } else {

        print '<td class="completion-progresscell">';

        // Display course completion status icon
        print $OUTPUT->pix_icon('i/completion-auto-' . $completiontype, $fulldescribe);

        print '</td>';
    }

    if ($csv) {
        $export->add_data($row);
    } else {
        print '</tr>';
    }
}

if ($csv) {
    $export->download_file();
} else {
    echo '</tbody>';
}

print '</table>';
print $pagingbar;

echo $OUTPUT->footer($course);

// Trigger a report viewed event.
$event = \report_completion\event\report_viewed::create(array('context' => $context));
$event->trigger();
