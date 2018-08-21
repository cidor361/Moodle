<?php

require_once('../../config.php');
require_once('unenrolpl_form.php');
//require_once(__DIR__ . '/../../../config.php');
//require_once($CFG->libdir.'/adminlib.php');

global $DB, $OUTPUT, $PAGE;

// Check for all required variables.
$courseid = required_param('courseid', PARAM_INT);

$blockid  = required_param ( 'blockid' , PARAM_INT ) ;
 // Далее найдите дополнительные переменные.
$id  = optional_param ( 'id' ,  0 , PARAM_INT ) ;


if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_unenrolpl', $courseid);
}

require_login($course);
$PAGE->set_url('/blocks/unenrolpl/report.php', array('id' => $courseid));
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('edithtml', 'block_unenrolpl'));

$coursecontext = context_course::instance($course->id);
$arrayofusers = get_enrolled_sql($coursecontext, '', 0, true);
$text = implode(' ', $arrayofusers);            //neet to output


$unenrolpl = new unenrolpl_form();
//$site = get_site();
echo $OUTPUT->header();
$unenrolpl->display();
echo $OUTPUT->footer();

/**
 * if ($unenrolpl->is_cancelled()) {
    // Cancelled forms redirect to the course main page.
    $courseurl = new moodle_url('/course/report.php', array('id' => $id));
    redirect($courseurl);
} else if (unenrolpl->get_data()) {
    // We need to add code to appropriately act on and store the submitted data
    // but for now we will just redirect back to the course main page.
    $courseurl = new moodle_url('/course/report.php', array('id' => $courseid));
    redirect($courseurl);
} else {
    // form didn't validate or this is the first display
    $site = get_site();
    echo $OUTPUT->header();
    $simplehtml->display();
    echo $OUTPUT->footer();
}
*/

?>