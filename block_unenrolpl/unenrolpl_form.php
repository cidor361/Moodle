<?php
require_once("{$CFG->libdir}/formslib.php");


class unenrolpl_form extends moodleform {

    function definition() {
        global $CFG, $DB, $COURSE;

        $mform =& $this->_form;
//         $mform->addElement('button', 'intro', get_string("buttonlabel"));
        $buttonarray=array();


        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('Unenrol', 'block_unenrolpl'));


//         $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);











        /**
        $mform->addElement('text', 'email', get_string('email')); // Add elements to your form
        $mform->setType('email', PARAM_NOTAGS);                   //Set type of element
        $mform->setDefault('email', 'Please enter email');
         */







        /**


        //$mform->addElement('hidden', 'blockid');
        //$mform->addElement('hidden', 'courseid');
        }*/
    }
}
