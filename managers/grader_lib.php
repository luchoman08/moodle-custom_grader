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
 * Grader Lib
 *
 * @author     Camilo José Cruz Rivera
 * @package    custom_grader
 * @copyright  2018 Camilo José Cruz Rivera <cruz.camilo@correounivalle.edu.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Queries from module grades record (registro de notas)
require_once (__DIR__ . '/../../../config.php');
global $CFG;
require_once 'wizard_lib.php';
require_once (__DIR__ . '/../classes/custom_grade_report_grader.php');
require_once $CFG->libdir . '/gradelib.php';
require_once $CFG->libdir . '/datalib.php';
require_once $CFG->dirroot . '/grade/lib.php';
require_once $CFG->dirroot . '/grade/report/user/lib.php';
require_once $CFG->dirroot . '/blocks/ases/managers/lib/student_lib.php';
require_once $CFG->dirroot . '/blocks/ases/managers/lib/lib.php';
require_once $CFG->dirroot . '/grade/report/grader/lib.php';
require_once $CFG->dirroot . '/grade/edit/tree/lib.php'; //grade_edit_tree
////////////////////////////////////////////////////////////////////////////////////////////
////SOLO RAMA UNIVALLE
use \local_customgrader\custom_grade_category;
/**
 * Gets course information given its id
 * @see get_info_students($id_curso)
 * @param $id_curso --> course id
 * @return array Containing all ases students in the course
 */
function get_info_students($id_curso)
{
    global $DB;
    $query_students = "SELECT usuario.id, usuario.firstname, usuario.lastname, usuario.username
    FROM {user} usuario INNER JOIN {user_enrolments} enrols ON usuario.id = enrols.userid
    INNER JOIN {enrol} enr ON enr.id = enrols.enrolid
    INNER JOIN {course} curso ON enr.courseid = curso.id
    WHERE curso.id= $id_curso AND usuario.id IN (SELECT user_m.id
                                                FROM {user} user_m
                                                INNER JOIN {talentospilos_user_extended} extended ON user_m.id = extended.id_moodle_user
                                                INNER JOIN {talentospilos_usuario} user_t ON extended.id_ases_user = user_t.id
                                                INNER JOIN {talentospilos_est_estadoases} estado_u ON user_t.id = estado_u.id_estudiante
                                                INNER JOIN {talentospilos_estados_ases} estados ON estados.id = estado_u.id_estado_ases
                                                WHERE estados.nombre = 'seguimiento')";

    $estudiantes = $DB->get_records_sql($query_students);
    return $estudiantes;
}
////////////////////////////////////////////////////////////////////////////////////////////



///******************************************///
///*** Get info global_grade_book methods ***///
///******************************************///

/**
 * Returns a string with the teacher from a course.
 *

 * @see getTeacher($id_curso)
 * @param $id_curso --> course id
 * @return string $teacher_name
 **/

function getTeacher($id_curso)
{
    global $DB;
    $query_teacher = "SELECT concat_ws(' ',firstname,lastname) AS fullname
    FROM
      (SELECT usuario.firstname,
              usuario.lastname,
              userenrol.timecreated
       FROM {course} cursoP
       INNER JOIN {context} cont ON cont.instanceid = cursoP.id
       INNER JOIN {role_assignments} rol ON cont.id = rol.contextid
       INNER JOIN {user} usuario ON rol.userid = usuario.id
       INNER JOIN {enrol} enrole ON cursoP.id = enrole.courseid
       INNER JOIN {user_enrolments} userenrol ON (enrole.id = userenrol.enrolid
                                                    AND usuario.id = userenrol.userid)
       WHERE cont.contextlevel = 50
         AND rol.roleid = 3
         AND cursoP.id = $id_curso
       ORDER BY userenrol.timecreated ASC
       LIMIT 1) AS subc";
    $profesor = $DB->get_record_sql($query_teacher);
    return $profesor->fullname;
}

/**
 * Return the grade report for a given course id
 * @param $course_id
 * @return custom_grade_report_grader
 */
function get_grade_report($course_id,  $load_final_grades=true, $load_users=true) {
    global $USER;
    $USER->gradeediting[$course_id] = 1;

    $context = context_course::instance($course_id);

    $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'user', 'courseid' => $course_id));
    $report = new custom_grade_report_grader($course_id, $gpr, $context);
    $report->get_right_rows(true);
    if($load_users) $report->load_users();
    if($load_final_grades) $report->load_final_grades();
    return $report;
}

/**
 * Returns a string html table with the students, categories and their notes.
 *

 * @see get_categories_global_grade_book($id_curso)
 * @param $id_curso --> course id
 * @return string HTML table
 **/
function get_categories_global_grade_book($id_curso)
{

    $grade_book = get_grade_report($id_curso);
    return $grade_book->get_grade_table();
}

///**********************************///
///***    Update grades methods   ***///
///**********************************///

/**
 * update all grades from a course which needsupdate
 * @see update_grade_items_by_course($course_id)
 * @param $course_id --> id from course to update grade_items
 * @return integer --> 1 if Ok 0 if not
 */

function update_grade_items_by_course($course_id)
{
    $grade_items = grade_item::fetch_all(array('courseid' => $course_id, 'needsupdate' => 1));
    foreach ($grade_items as $item) {
        if ($item->needsupdate === 1) {
            $item->regrading_finished();
        }
    }
    return '1';
}

class GraderInfo {
    public $course;
    public $items;
    public $students;
    public $categories;
    public $grades;
    public $levels;
}

/**
 * Return all info of grades in a course normalized
 * @param $courseid
 * @param bool $fillers
 * @return GraderInfo
 * @throws dml_exception
 */
function get_normalized_all_grade_info($courseid){

    $grade_info = new GraderInfo();
    $grade_tree_fills  = new grade_tree($courseid, true, true);
    $grade_report = get_grade_report($courseid);
    $grade_tree = new grade_tree($courseid, false);
    $items = $grade_tree->items;
    $categories = custom_grade_category::fetch_all(array('courseid'=>$courseid));
    $students =  $grade_report->users;
    $student_grades = $grade_report->get_all_grades();
    $course = get_course($courseid);
    $grade_info->course = $course;
    $grade_info->items = array_values($items);
    $grade_info->categories = array_values($categories);
    $grade_info->students = array_values($students);
    $grade_info->levels = $grade_tree_fills->get_levels();
    $grade_info->grades = array_values($student_grades);
    return $grade_info;

}

/**
 * Get student grades for a course
 * @param $courseid number
 * @return array List of student grades for a course
 */
function get_student_grades($courseid, $itemid=null, $userid=null){
    $grade_report = get_grade_report($courseid, false);
    $student_grades = $grade_report->get_all_grades($itemid, $userid);
    return array_values($student_grades);
}
/**
 * Get student grades for a item
 * @param $course_id number
 * @param $item_id number
 * @return array List of student grades for a course
 * @throws dml_exception
 */
function get_student_grades_for_item($course_id, $item_id){
    $grade_report = get_grade_report($course_id, false);
    $student_grades = $grade_report->get_all_grades($item_id);
    return array_values($student_grades);
}

/**
 * It performs the insertion of 'parcial'
 *
 * @param $course --> course id
 * @param $father --> category parent
 * @param $name --> category name
 * @param $weighted --> type of qualification(aggregation)
 * @param $weight --> weighted value
 * @return array|false --- ok-> 1 || error-> 0
 **/
function insertParcial($course, $father, $name, $weighted, $weight)
{
    global $DB;
    $transaction = $DB->start_delegated_transaction();
    /** @var grade_category|false $category_or_false */
    $category_or_false = insertCategoryParcial($course, $father, $name, $weighted, $weight);

    if ($category_or_false !== false) {
        $partial_item_or_false = insertItem($course, $category_or_false->id, $name, 0) ;
        if ($partial_item_or_false !== false) {
            $optional_item_or_false = insertItem($course, $category_or_false->id, "Opcional de " . $name, 0) ;
            if ($optional_item_or_false !== false) {
                $DB->commit_delegated_transaction($transaction);
                return array(
                    'category'=>$category_or_false,
                    'partial_item'=>$partial_item_or_false,
                    'optional_item'=>$optional_item_or_false
                );
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * Performs the insertion of a category 'parcial'. Returns the id  the created category if it's successful, 0 otherwise
 *
 * @see insertCategoryParcial($course,$father,$name,$weighted,$weight)
 * @param $course --> course id
 * @param $father --> category parent
 * @param $name --> category name
 * @param $weighted --> type of qualification(aggregation)
 * @param $weight --> weighted value
 * @return false|grade_category --- ok->id_cat || error->0
 **/
function insertCategoryParcial($course, $father, $name, $weighted, $weight)
{
    global $DB;

    //Instance an object category to use insert_record
    $object = new stdClass;
    $object->courseid = $course;
    $object->fullname = $name;
    $object->parent = $father;
    $object->aggregation = $weighted;
    $object->timecreated = time();
    $object->timemodified = $object->timecreated;
    $object->aggregateonlygraded = 0;
    $object->aggregateoutcomes = 0;

    $succes = $DB->insert_record('grade_categories', $object);

    if ($succes) {
        if (insertItem($course, $succes, $name, $weight, true) !== false) {
            return grade_category::fetch(array('id'=>$succes));
        } else {
            return false;
        }
    }

    return false;
}

/**
 * Edit category
 * @param grade_category $category
 * @return bool|grade_category
 */
function editCategory($category) {
    $edited =  edit_category(
        $category->courseid,
        $category->id,
        $category->aggregationcoef,
        $category->fullname,
        $category->parent_category,
        $category->aggregation,
        $category->courseid);
    if ( $edited ) {
        return custom_grade_category::fetch(array('id'=>$category->id));
    } else {
        return false;
    }
}

/**
 * Deprecated method Instead use insert_category($params)
 *
 * It performs the insertion of a category considering whether it is of weighted type or not,
 * then it inserts the item that represents the category. The last one is needed for the category to have a weight.
 *
 * @param $course --> course id
 * @param $father --> category parent
 * @param $name --> category name
 * @param $weighted --> type of qualification(aggregation)
 * @param $weight --> weighetd value
 * @return array|false --- ['category'=> g: grade_category, 'category_item'=>i: grade_item] || false
 **/

function insertCategory($course, $father, $name, $weighted, $weight)
{
    global $DB;

    //Instance a category object to use insert_record
    $object = new stdClass;
    $object->courseid = $course;
    $object->fullname = $name;
    $object->parent = $father;
    $object->aggregation = $weighted;
    $object->timecreated = time();
    $object->timemodified = $object->timecreated;
    $object->aggregateonlygraded = 0;
    $object->aggregateoutcomes = 0;
    $transaction = $DB->start_delegated_transaction();
    $category_id = $DB->insert_record('grade_categories', $object);

    if ($category_id) {
        /** @var grade_category|false $category_item_or_false */
        $category_item_or_false = insertItem($course, $category_id, $name, $weight, true);
        $category_item = grade_item::fetch(array('id'=>$category_item_or_false->id));
        $category = grade_category::fetch(array('id'=>$category_id));
        $category_grade_item = $category->get_grade_item();
        $category->grade_item = $category_grade_item->id;
        if ($category_item !== false) {
            $DB->commit_delegated_transaction($transaction);
            return array('category'=> $category, 'category_item'=> $category_item);
        } else {
            return false;
        }
    }
    return false;
}


/**
 * Inserts an item, either flat item or an item related to a category, the last one is needed to assign a weight in case the category were a
 * daughter of another category with weighted rating
 *
 * @see insertItem($course,$father,$name,$valsend,$item)
 * @param $course --> course id
 * @param $father --> category parent
 * @param $name --> category name
 * @param $aggregationcoef --> $aggregationcoef value
 * @param $is_category_item --> Item that'll be added
 * @return bool|int true or new id
 * @throws dml_exception
 */
function insertItem($course, $father, $name, $aggregationcoef, $is_category_item=false)
{
    global $DB;
    //Instance an object item to use insert_record
    if (!$is_category_item) {
        $object = new stdClass;
        $object->courseid = $course;
        $object->categoryid = $father;
        $object->itemname = $name;
        $object->itemnumber = 0;
        $object->itemtype = 'manual';
        $object->sortorder = getNextIndex($course);
        $object->aggregationcoef = $aggregationcoef;
        $object->grademax = 5;
    } else {
        $object = new stdClass;
        $object->courseid = $course;
        $object->itemtype = 'category';
        $object->sortorder = getNextIndex($course);
        $object->aggregationcoef = $aggregationcoef;
        $object->iteminstance = $father;
        $object->grademax = 5;
    }

    $item_id_or_false = $DB->insert_record('grade_items', $object);
    if($item_id_or_false) {
        return grade_item::fetch(array('id'=>$item_id_or_false));
    } else {
        return false;
    }
}


/**
 * @param $category grade_category
 * @return $category object
 */
function _append_category_grade_item($category) {
    $_category = (object) $category;
    $category_item =  $category->get_grade_item();
    $_category->grade_item = $category_item->id;
    return $_category;
}
function _append_category_grade_item_for_array(array $categories): array {
    return array_map(function($c) {return _append_category_grade_item($c);}, $categories);
}
function get_table_levels($courseid, $fillers = true, $category_grade_last=true){
    $grade_tree = new grade_tree($courseid, $fillers, $category_grade_last);
    return $grade_tree->get_levels();
}
//update_grade_items_by_course(9);

/**
 * Updates grades from a student
 *

 * @see update_grades_moodle($userid, $itemid, $finalgrade,$courseid)
 * @param $userid --> user id
 * @param $item --> item id
 * @param $finalgrade --> grade value
 * @param $courseid --> course id
 *
 * @return boolean --> true if there's a successful update, false otherwise.

 */

function update_grades_moodle($userid, $itemid, $finalgrade, $courseid)
{
    if (!$grade_item = grade_item::fetch(array('id' => $itemid, 'courseid' => $courseid))) { // we must verify course id here!
        return false;
    }

    if ($grade_item->update_final_grade($userid, $finalgrade, 'gradebook', false, FORMAT_MOODLE)) {
        $resp = new stdClass;
        $resp->nota = true;
        return $resp;
    } else {

        $resp = new stdClass;
        $resp->nota = false;

        return $resp;
    }

}

/**
 * Updates grades from a student
 *

 * @see update_grades_moodle($userid, $itemid, $finalgrade,$courseid)
 * @param $userid --> user id
 * @param $item --> item id
 * @param $finalgrade --> grade value
 * @param $courseid --> course id
 *
 * @return bool Return true if the grade exist and was updated, false otherwise

 */

function update_grades_moodle_($userid, $itemid, $finalgrade, $courseid)
{
    if (!$grade_item = grade_item::fetch(array('id' => $itemid, 'courseid' => $courseid))) { // we must verify course id here!
        return false;
    }
    $updated  = $grade_item->update_final_grade($userid, $finalgrade, 'gradebook', false, FORMAT_MOODLE);
    return $updated;
}

