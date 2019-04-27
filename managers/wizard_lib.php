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
 * Wizard Categories Lib
 *
 * @author     Camilo José Cruz Rivera
 * @package    custom_grader
 * @copyright  2018 Camilo José Cruz Rivera <cruz.camilo@correounivalle.edu.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once $CFG->libdir . '../../lib/gradelib.php';
require_once $CFG->dirroot . '/grade/lib.php';
require_once $CFG->dirroot . '/grade/report/user/lib.php';

///*********************************///
///*** Wizard categories methods ***///
///*********************************///

const CATEGORY_ELEMENT = 'cat';
const ITEM_ELEMENT = 'row';
const PROMEDIO_PONDERADO = 10;
const PROMEDIO_SIMPLE = 1;
/** INSERTION METHODS **/

/**
 *  Insert category using grade core
 *
 * @param $father --> category parent
 * @param $name --> category name
 * @param $weighted --> type of qualification(aggregation)
 * @param $weight --> weighetd value
 * @return integer --- ok->1 || error->0
 */

function insert_category($course, $father, $name, $weighted, $weight)
{
    $params = array(
        'courseid' => $course,
        'fullname' => $name,
        'parent' => $father,
        'aggregation' => $weighted,
    );

    $category = grade_category::fetch($params);
    if($category->insert()){
        return 1;
    }else{
        return 0;
    }
}







/** EDITING METHODS **/

/**
 * Edit a category
 * @see edit_category($courseid, $categoryid, $weight, $name, $parentid,$aggregation)
 * @param $courseid --> course id
 * @param $categoryid --> category id
 * @param $weight --> weighted value
 * @param $name --> category name
 * @param $parentid --> parent id
 * @param $aggregation --> qualification type id
 * @return boolean true if category and item were both updated, false otherwise
 */
function edit_category($courseid, $categoryid, $aggregationcoef, $name, $parentid, $aggregation, $course_cat)
{
    if ($grade_category = grade_category::fetch(array('id' => $categoryid, 'courseid' => $courseid))) {

        $grade_item = $grade_category->get_grade_item();

        if (!$grade_category->is_course_category()) {

            if ($grade_category->fullname != $name) {
                $grade_category->fullname = $name;
            }

            if ($grade_category->parent != $parentid) {
                $grade_category->set_parent($parentid);
            }

            $parent_category = $grade_category->get_parent_category();

            if ($parent_category->aggregation != 10) {
                $grade_item->aggregationcoef = 0;
            } else if ($grade_item->aggregationcoef != $aggregationcoef) {
                $grade_item->aggregationcoef = $aggregationcoef;
            }

            if ($grade_item->aggregationcoef == 0 and $parent_category->aggregation == 10) {
                $grade_item->aggregationcoef = 1;
            }

            if ($grade_item->update()) {
                $grade_item->regrading_finished();
                $item_update = true;
            } else {
                $item_update = false;
            }
        } else {
            $item_update = true;
        }

        if ($grade_category->aggregation != $aggregation and !($aggregation === false)) {
            $grade_category->aggregation = $aggregation;
            $new_agg = true;
        }
        if ($new_agg and $grade_category->aggregation == 10) {
            // weight value = 1 to children
            $children = $grade_category->get_children();

            foreach ($children as $child) {
                $item = $child['object'];
                if ($child['type'] == 'category') {
                    $item = $item->load_grade_item();
                }

                // Set the new aggregation fields.
                $item->aggregationcoef = 1;
                $item->update();
                $item->regrading_finished();

            }

        } else if ($new_agg and $grade_category->aggregation != 10) {
            //weight value = 0 to children
            $children = $grade_category->get_children();

            foreach ($children as $child) {
                $item = $child['object'];
                if ($child['type'] == 'category') {
                    $item = $item->load_grade_item();
                }

                // Set the new aggregation fields.
                $item->aggregationcoef = 0;
                $item->update();
                $item->regrading_finished();

            }
        }

        if ($grade_category->update()) {
            $grade_item->regrading_finished();
            $category_update = true;
            $course_item = grade_item::fetch_course_item($courseid);
            $course_item->regrading_finished();
        } else {

            $category_update = false;
        }

        if ($category_update and $item_update) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * Edits an item given some specifications, including course id and weight value
 *
 * @see edit_item($courseid, $itemid, $weight, $name, $parentid)
 * @param $courseid --> course id
 * @param $itemid --> item id
 * @param $aggregationcoef --> weighted value
 * @param $name --> item name
 * @param $parentid --> parent id
 * @return boolean true if grade item was updated, false otherwise
 */
function edit_item($courseid, $itemid, $aggregationcoef, $name, $parentid)
{
    if ($grade_item = grade_item::fetch(array('id' => $itemid, 'courseid' => $courseid))) {

        if ($grade_item->itemname != $name and $name != "Enlazar a la actividad Tarea") {
            $grade_item->itemname = $name;
        }

        if ($grade_item->parentcategory != $parentid) {
            $grade_item->set_parent($parentid, false);
        }

        $parent_category = $grade_item->get_parent_category();
        if ($grade_item->itemtype != 'category' && $parent_category->aggregation != PROMEDIO_PONDERADO) {
            $grade_item->aggregationcoef = 0;
        } else if ($grade_item->aggregationcoef != $aggregationcoef) {
            $grade_item->aggregationcoef = $aggregationcoef;
        }

        if ($grade_item->aggregationcoef == 0 and $parent_category->aggregation == PROMEDIO_PONDERADO) {
            $grade_item->aggregationcoef = 1;
        }

        if ($grade_item->update()) {
            $grade_item->regrading_finished();
            $course_item = grade_item::fetch_course_item($courseid);
            $course_item->regrading_finished();

            return true;
        } else {
            return false;
        }
    }
}


/**
 * Edit item
 * @param grade_item $item
 * @return bool|grade_item
 */
function editItem($item) {
    $edited =  edit_item(
        $item->courseid,
        $item->id,
        $item->aggregationcoef,
        $item->itemname,
        $item->categoryid);
    if ( $edited ) {
        $_item = grade_item::fetch(array('id'=>$item->id));
        return $_item;
    } else {
        return false;
    }
}

/** DELETING METHODS **/


function delete_item($item_id, $courseid) {
    return delete_element($item_id, $courseid, ITEM_ELEMENT);
}
function delete_category($category_id, $courseid) {
    return delete_element($category_id, $courseid, CATEGORY_ELEMENT);
}


/**
 * Deletes an element of grading. (item or category)
 * @see delete_element($id, $courseid,$type)
 * @param $id --> element id to delete
 * @param $courseid --> course id
 * @param $type --> element type. "cat" if it's category, "row" if it's item
 * @return boolean true if it was deleted, false otherwise
 */
function delete_element($id, $courseid, $type)
{
    global $DB;
    $gpr = new grade_plugin_return(array('type' => 'edit', 'plugin' => 'tree', 'courseid' => $courseid));
    $gtree = new grade_tree($courseid, false, false);

    if ($type === 'cat') {
        $eid = "cg$id";
    } elseif ($type === 'row') {
        $eid = "ig$id";
    }

    if (!$element = $gtree->locate_element($eid)) {
        return false;
    }
    $object = $element['object'];
    $object->delete();
    //sleep(5);
    $query = "SELECT id FROM {grade_items} WHERE needsupdate = 1 AND courseid = $courseid";
    $result = $DB->get_records_sql($query);

    foreach ($result as $itemid) {
        $grade_item = grade_item::fetch(array('id' => $itemid->id, 'courseid' => $courseid));
        if (!$grade_item->is_course_item()) {
            $grade_item->aggregationcoef = 1;
            $grade_item->update();
        }
        $grade_item->regrading_finished();
    }

    return true;
}

/** AUXILIARY METHODS OF WIZARD **/

//
/**
 * Makes a query to find the last index of the sorted element corresponding to the category that is being inserted
 *
 * @see getNextIndex($course)
 * @param $course --> course id
 * @return integer
 **/
function getNextIndex($course)
{
    global $DB;
    $sql_query = "SELECT max(sortorder) FROM {grade_items} WHERE courseid=" . $course . ";";
    $output = $DB->get_record_sql($sql_query);
    $nextindex = ($output->max) + 1;
    return $nextindex;
}

/**
 * Makes an html_string with the categories tree of a course identified by $courseid
 *
 * @see getCategoriesandItems($courseid)
 * @param $courseid --> course id
 * @return string html
 **/
function getCategoriesandItems($courseid)
{

    global $DB;

    $sql_query = "SELECT {user_enrolments}.userid AS id
                FROM {enrol} INNER JOIN {user_enrolments} ON ({user_enrolments}.enrolid ={enrol}.id)
                WHERE courseid=" . $courseid . "
                LIMIT 1;";

    $userid = $DB->get_record_sql($sql_query)->id;
    $context = context_course::instance($courseid);
    //print_r($userid);

    $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'user', 'courseid' => $courseid, 'userid' => $userid));
    $report = new grade_report_user($courseid, $gpr, $context, $userid);
    reduce_table_categories($report);
    if ($report->fill_table()) {
        return print_table_categories($report);
    }
}

/**
 * Function that reduces grade information to display in categories tree
 *
 * @see reduce_table_categories(&$report)
 * @param &$report --> object containing grade information
 * @return null
 */
function reduce_table_categories(&$report)
{
    $report->showpercentage = false;
    $report->showrange = false;
    $report->showfeedback = false;
    $report->showcontributiontocoursetotal = false;
    $report->showweight = false;
    $report->showgrade = false;
    $report->showtotalsifcontainhidden = false;
    $report->setup_table();
}

/**
 * Returns the id of the parent category of an item
 *
 * @see get_id_parent_item($id, $courseid)
 * @param $id --> item id
 * @param $courseid --> course id
 * @return boolean
 **/
function get_id_parent_item($id, $courseid)
{
    $grade_item = grade_item::fetch(array('id' => $id, 'courseid' => $courseid));
    return ($grade_item->get_parent_category()->id);
}

/**
 * Returns the category parent id
 *
 * @see get_id_parent_category($id)
 * @param $id --> category id
 * @return boolean|string --> false if there's no id, string with the id otherwise
 */
function get_id_parent_category($id)
{
    if ($grade_category = grade_category::fetch(array('id' => $id))) {
        if (!$grade_category->is_course_category()) {
            return ($grade_category->get_parent_category()->id);
        }
    } else {
        return false;
    }
}

/**
 * Returns true if an item is a Mod type, false otherwise
 *
 * @see isItemMod($id, $courseid)
 * @param $id --> item id
 * @param $courseid --> course id
 * @return boolean
 **/
function isItemMod($id, $courseid)
{
    $grade_item = grade_item::fetch(array('id' => $id, 'courseid' => $courseid));
    return ($grade_item->is_external_item());
}

/**
 * Returns true if a category is a course type
 *
 * @see isCourseCategorie($id, $courseid)
 * @param $id --> category id
 * @param $courseid --> course id
 * @return boolean
 **/
function isCourseCategorie($id, $courseid)
{
    $grade_categorie = grade_category::fetch(array('id' => $id, 'courseid' => $courseid));
    return ($grade_categorie->is_course_category());
}

/**
 * Gets the max weight that a new item can have in a category.
 *
 * @see getMaxWeight($categoryid)
 * @param $categoryid --> category id
 * @return integer
 */
function getMaxWeight($categoryid)
{
    global $DB;
    $maxweight = 100;

    $query = "SELECT sum(peso) as total
            FROM
              (SELECT id,
                      SUM(aggregationcoef) AS peso
               FROM {grade_items}
               WHERE categoryid = $categoryid
               GROUP BY id
               UNION SELECT item.id,
                            SUM(item.aggregationcoef) AS peso
               FROM {grade_items} item
               INNER JOIN {grade_categories} cat ON item.iteminstance=cat.id
               WHERE cat.parent = $categoryid
               GROUP BY item.id)AS pesos";
    $result = $DB->get_record_sql($query);

    if ($result) {
        $weight = $result->total;
    } else {
        $weight = 0;
    }

    $maxweight = $maxweight - $weight;

    return $maxweight;
}

/**
 * Function that searches into $string "gradeitemdescriptionfiller" to determine if it's a total item
 *
 * @see isCategoryTotal($string)
 * @param $string --> Describes an item
 * @return boolean false if it's not a total item, true otherwise
 */
function isCategoryTotal($string)
{
    if (stripos($string, "gradeitemdescriptionfiller") === false && stripos($string, "Total") == false) {
        return false;
    } else {
        return true;
    }

}

/**
 * Function that searches into $string "Categoria" or "Category"
 *
 * @see isCategory($string)
 * @param $string --> Describes a potential category
 * @return boolean
 **/

function isCategory($string)
{
    if ((stripos($string, "Categoría") === false) && (stripos($string, "Category") === false)) {
        return false;
    } else {
        return true;
    }

}

/**
 * Gets an item weight.
 *
 * @see getweightofItem($itemid)
 * @param $itemid --> item id
 * @return integer
 **/
function getweightofItem($itemid)
{
    global $DB;

    $sql_query = "SELECT aggregationcoef as weight
                  FROM {grade_items}
                  WHERE id = " . $itemid;

    $output = $DB->get_record_sql($sql_query);
    if ($output) {
        $weight = $output->weight;
        return $weight;
    }
    return false;
}

/**
 * Gets a category weight.
 *
 * @param $itemid
 * @return int weight
 **/
function getweightofCategory($id)
{
    global $DB;

    $sql_query = "SELECT aggregationcoef as weight
                  FROM {grade_items} item INNER JOIN {grade_categories} cat on item.iteminstance=cat.id
                  WHERE cat.id = " . $id . " AND itemtype = 'category'";

    $output = $DB->get_record_sql($sql_query);
    if ($output) {
        $weight = $output->weight;
        return $weight;
    }
    return false;
}

/**
 * Gets element name
 *
 * @see getElementName($elementid, $type)
 * @param $elementid --> element id
 * @param $type --> 'cat' or 'it'
 * @return string
 */

 function getElementName($element, $type)
 {
    if($type == 'cat'){
         $consulta = "SELECT fullname as name from {grade_categories} where id = $element";
    }elseif($type == 'it'){
        $consulta = "SELECT itemname as name from {grade_items} where id = $element";
    }
     global $DB;
     
     $result = $DB->get_record_sql($consulta)->name;
     
     
     return $result;
 }


 


/**
 * Gets a category aggregation value
 *
 * @see getAggregationofCategory($categoryid)
 * @param $categoryid --> category id
 * @return integer
 */

function getAggregationofCategory($categoryid)
{
    global $DB;

    $sql_query = "
        SELECT aggregation
        FROM {grade_categories}
        WHERE id = '$categoryid'";
    $output = $DB->get_record_sql($sql_query);

    $aggregation = $output->aggregation;

    return $aggregation;
}

/**
 * Gets a category aggregation type.
 *
 * @see getParentCategories($id_course,$id_element,$type)
 * @param $id_course --> course id
 * @param $id_element --> element id
 * @param $type --> Category type
 * @return integer
 */

function getParentCategories($id_course, $id_element, $type)
{
    global $DB;
    if ($type == "it") {
        $query = "SELECT categoryid FROM {grade_items} WHERE id = $id_element";
        $id_parent = $DB->get_record_sql($query)->categoryid;
    } else {
        $query = "SELECT parent FROM {grade_categories} WHERE id = $id_element";
        $id_parent = $DB->get_record_sql($query)->parent; // NULL WHEN TOTAL COURSE CATEGORY
    }
    $record = new stdClass;
    if (!$id_parent) {
        $record->total = true;
        return $record;
    }
    $query_categories = "SELECT cat.id as id, cat.fullname as cat_name, cur.fullname as cur_name
                         FROM {grade_categories} cat INNER JOIN {course} cur
                         ON cat.courseid = cur.id
                         WHERE cat.courseid = $id_course";
    $output = $DB->get_records_sql($query_categories);
    $html_string = "";
    foreach ($output as $categorie) {
        if ($categorie->cat_name == '?') {
            $categorie->cat_name = $categorie->cur_name;
        }
        if ($categorie->id == $id_parent) {
            $html_string .= "<option value = '$categorie->id' selected> $categorie->cat_name </option>";
        } else {
            $html_string .= "<option value = '$categorie->id'> $categorie->cat_name </option>";
        }

    }

    $record->html = $html_string;
    return $record;
}
