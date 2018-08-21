<?php
require_once("{$CFG->libdir}/formslib.php");

class unenrolpl_form extends moodleform {

    function definition() {

        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', get_string('textfields', 'block_unenrolpl'));
//        $mform->addElement('button', 'intro', get_string('buttonCancel', 'block_unenrolpl'));
//        $mform->addElement('button', 'intro', get_string('buttonUnenrol', 'block_unenrolpl'));

        //normally you use add_action_buttons instead of this code
        $buttonarray=array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges', 'block_unenrolpl'));
//        $buttonarray[] = $mform->createElement('reset', 'resetbutton', get_string('resert', 'block_unenrolpl'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
        // hidden elements
        $mform->addElement('hidden', 'blockid');
        $mform->addElement('hidden', 'courseid');
    }
}