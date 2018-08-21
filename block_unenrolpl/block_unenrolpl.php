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

/**
 * Block unenrolpl is defined here.
 *
 * @package     block_unenrolpl
 * @copyright   2018 Igor <cidor361@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * unenrolpl block.
 *
 * @package    block_unenrolpl
 * @copyright  2018 Igor <cidor361@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_unenrolpl extends block_base {

    /**
     * Initializes class member variables.
     */
    public function init() {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_unenrolpl');
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content() {
        global $COURSE, $DB;

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
//            $this->content->text = 'list of users';
            $context = context_course::instance(2);         //input current id
            $arrayofusers = get_enrolled_sql($context, '', 0, true);
            $text = implode(' ', $arrayofusers);            //neet to output
            $this->context->text = $text;

// The other code.
            $url = new moodle_url('/blocks/unenrolpl/report.php', array('blockid' => $this->instance->id, 'courseid' => $COURSE->id));
            $this->content->footer = html_writer::link($url, get_string('newpage', 'block_unenrolpl'));

//            $context = context_course::instance($COURSE->id);
            //$arrayofusers = get_enrolled_sql($context, '', 0, true);
            //$arrayofusers = get_enrolled_join($context, 3, false);
            ////$arrayofusers = get_enrolled_users($context, '', 0, 'u.*',  null, 0, 0, false);
            ////$text = count_enrolled_users($context, '', 0, false);		//num of users
            //$text = $COURSE->id;
            //$arrayy = get_role_users(5 , $context);
//            $text = implode('|', $arrayofusers);

//            $arrayofusers = get_users_by_capability($context, '');
//            $students = get_role_users(5 , $context);

//            $this->content->text = $text;



/**
            $courseid = required_param('courseid', PARAM_INT);
            $context = context_course::instance($courseid);
            $userfields = user_picture::fields('u', array('username'));
            $from = "FROM {user} u
            INNER JOIN {role_assignments} a ON a.userid = u.id
            LEFT JOIN {ranking_points} r ON r.userid = u.id AND r.courseid = :r_courseid
            INNER JOIN {context} c ON c.id = a.contextid";

            $where = "WHERE a.contextid = :contextid
            AND a.userid = u.id
            AND a.roleid = :roleid
            AND c.instanceid = :courseid";

            $params['contextid'] = $context->id;
            $params['roleid'] = 5;
            $params['courseid'] = $COURSE->id;
            $params['r_courseid'] = $params['courseid'];

            $order = "ORDER BY r.points DESC, u.firstname ASC
            LIMIT " . $perpage;

            $sql = "SELECT $userfields, r.points $from $where $order";
            $students = array_values($DB->get_records_sql($sql, $params));


            //get_instance_name($instance);
            //unenrol_user(stdClass $instance, $userid);
            $this->content->text = ($students);
*/
        }
        return $this->content;
    }

}