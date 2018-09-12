<?php
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

require_once("{$CFG->libdir}/completionlib.php");
$cinfo = new completion_info($course_object);
$iscomplete = $cinfo->is_course_complete($USER->id);

class block_unenrolpl extends block_base {

    public function init() {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_unenrolpl');
    }

    public function get_content() {
    
        global $COURSE;
        
        if ($this->content !== null) {
            return $this->content;
        }
        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }
        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        if (!empty($this->config->text)) {
            $this->content->text = $this->config->text;
        } else {
            $url = new moodle_url('/blocks/unenrolpl/report.php', array('blockid' => $this->instance->id, 'courseid' => $COURSE->id));
            $this->content->footer = html_writer::link($url, get_string('Listofcompleteusers', 'block_unenrolpl'));
            
            $context = context_course::instance($COURSE->id);

            $arrayofusers = get_role_users(5 , $context, false, 'u.id');
            
            $table = new html_table();
            $table->id = "whodat";
            $table->data = $arrayofusers;
            $table->caption = "Who even knows?";
            $table->captionhide = true;
            $this->content->text = html_writer::table($table);
            
//            $iscomplete = $cinfo->is_course_complete(id);
        }
        return $this->content;
    }

}
