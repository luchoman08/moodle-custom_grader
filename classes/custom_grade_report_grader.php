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
 * Custom grader report for ASES utitlities
 *
 * @author     Luis Gerardo Manrique Cardona
 * @package    block_ases
 * @copyright  2018 Luis Gerardo Manrique Cardona <luis.manrique@correounivalle.edu.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once $CFG->dirroot . '/grade/report/grader/lib.php';
class custom_grade_report_grader extends grade_report_grader {
    /**
     * Users array of the report grader
     * This function is only initialized when the method *$this->load_users* is called
     * @see $this->load_users
     * @var $users array
     */
    public $users;
    public $baseurl;
    public $allgrades;
    public $userselect_params;
    public function get_all_grades() {
        global $DB;
        // please note that we must fetch all grade_grades fields if we want to construct grade_grade object from it!
        $params = array_merge(array('courseid'=>$this->courseid), $this->userselect_params);
        $sql = "SELECT g.*
                  FROM {grade_items} gi,
                       {grade_grades} g
                 WHERE g.itemid = gi.id AND gi.courseid = :courseid {$this->userselect}";
        $allgradeitems = $this->get_allgradeitems();
        $userids = array_keys($this->users);
        $grades = [];
        if ($grades = $DB->get_records_sql($sql, $params)) {
            foreach ($grades as $graderec) {
                if (!empty($allgradeitems[$graderec->itemid])) {
                    $grade = new grade_grade($graderec, false);
                    array_push($grades, $grade);
                }
                if (in_array($graderec->userid, $userids) and array_key_exists($graderec->itemid, $this->gtree->get_items())) { // some items may not be present!!
                    array_push($grades, $grade);
                }
            }
        }
        return $grades;

    }
}