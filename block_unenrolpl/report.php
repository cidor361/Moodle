<?php
require_once('../../config.php');
require_once('unenrolpl_form.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->libdir.'/tablelib.php');

//require_once($CFG->libdir.'/adminlib.php');
global $DB, $OUTPUT, $PAGE;

// Check for all required variables.
$courseid = required_param('courseid', PARAM_INT);
$blockid  = required_param ( 'blockid' , PARAM_INT );

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

$context = context_course::instance($course->id);
$arrayofusers = get_enrolled_sql($context, '', 0, true);
$text = implode(' ', $arrayofusers);            //neet to output
$unenrolpl = new unenrolpl_form();

//$site = get_site();
echo $OUTPUT->header();
$unenrolpl->display();
echo $OUTPUT->footer();

//user_list_view($course, $context);
//echo user_get_participants_sql($courseid, 0);
//$output = $PAGE->get_renderer('tool_demo');
//print_object(user_get_participants_sql($courseid, 0));

?>
