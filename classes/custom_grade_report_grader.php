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
     * Builds and returns the rows that will make up the right part of the grader report
     * @param boolean $displayaverages whether to display average rows in the table
     * @return array Array of html_table_row objects
     */
    public function custom_get_right_rows($displayaverages) {
        global $CFG, $USER, $OUTPUT, $DB, $PAGE;

        $rows = array();
        $this->rowcount = 0;
        $numrows = count($this->gtree->get_levels());
        $numusers = count($this->users);
        $gradetabindex = 1;
        $columnstounset = array();
        $strgrade = $this->get_lang_string('grade');
        $strfeedback  = $this->get_lang_string("feedback");
        $arrows = $this->get_sort_arrows();

        $jsarguments = array(
            'cfg'       => array('ajaxenabled'=>false),
            'items'     => array(),
            'users'     => array(),
            'feedback'  => array(),
            'grades'    => array()
        );
        $jsscales = array();

        // Get preferences once.
        $showactivityicons = $this->get_pref('showactivityicons');
        $quickgrading = $this->get_pref('quickgrading');
        $showquickfeedback = $this->get_pref('showquickfeedback');
        $enableajax = $this->get_pref('enableajax');
        $showanalysisicon = $this->get_pref('showanalysisicon');

        // Get strings which are re-used inside the loop.
        $strftimedatetimeshort = get_string('strftimedatetimeshort');
        $strexcludedgrades = get_string('excluded', 'grades');
        $strerror = get_string('error');

        $viewfullnames = has_capability('moodle/site:viewfullnames', $this->context);

        foreach ($this->gtree->get_levels() as $key => $row) {
            $headingrow = new html_table_row();
            $headingrow->attributes['class'] = 'heading_name_row';

            foreach ($row as $columnkey => $element) {
                $sortlink = clone($this->baseurl);
                if (isset($element['object']->id)) {
                    $sortlink->param('sortitemid', $element['object']->id);
                }


                $eid    = $element['eid'];

                $object = $element['object'];
                $type   = $element['type'];
                $categorystate = @$element['categorystate'];

                if (!empty($element['colspan'])) {
                    $colspan = $element['colspan'];
                } else {
                    $colspan = 1;
                }

                if (!empty($element['depth'])) {

                    $catlevel = 'catlevel'.$element['depth'];
                } else {
                    $catlevel = '';
                }

                // Element is a filler
                if ($type == 'filler' or $type == 'fillerfirst' or $type == 'fillerlast') {
                    $fillercell = new html_table_cell();
                    $fillercell->attributes['class'] = $type . ' ' . $catlevel;
                    $fillercell->colspan = $colspan;
                    $fillercell->text = '&nbsp;';

                    // This is a filler cell; don't use a <th>, it'll confuse screen readers.
                    $fillercell->header = false;
                    $headingrow->cells[] = $fillercell;
                } else if ($type == 'category') {
                    /** @var $object grade_category */
                    // Make sure the grade category has a grade total or at least has child grade items.
                    if (grade_tree::can_output_item($element)) {
                        // Element is a category.
                        $categorycell = new html_table_cell();
                        $categorycell->attributes['class'] = 'category ' . $catlevel;
                        $categorycell->colspan = $colspan;
                        $categorycell->text = $this->get_course_header($element);
                        $categorycell->header = true;
                        $categorycell->scope = 'col';
                        $categorycell->attributes['data-categoryid'] = $object->id;
                        $categorycell->attributes['data-parent-categoryid'] = $object->parent;

                        // Print icons.
                        if ($USER->gradeediting[$this->courseid]) {
                            $categorycell->text .= $this->get_icons($element);
                        }

                        $headingrow->cells[] = $categorycell;
                    }
                } else {
                    // Element is a grade_item
                    if ($element['object']->id == $this->sortitemid) {
                        if ($this->sortorder == 'ASC') {
                            $arrow = $this->get_sort_arrow('up', $sortlink);
                        } else {
                            $arrow = $this->get_sort_arrow('down', $sortlink);
                        }
                    } else {
                        $arrow = $this->get_sort_arrow('move', $sortlink);
                    }

                    $headerlink = $this->gtree->get_element_header($element, true, $showactivityicons, false, false, true);

                    $itemcell = new html_table_cell();
                    /** @var $item grade_item */
                    $item = $element['object'] ;
                    $itemcell->attributes['class'] = $type . ' ' . $catlevel . ' highlightable'. ' i'. $item->id;
                    $itemcell->attributes['data-itemid'] = $item->id;
                    $parent_category = $item->get_parent_category();
                    $itemcell->attributes['data-parent-categoryid'] = $parent_category->id;
                    if ($element['object']->is_hidden()) {
                        $itemcell->attributes['class'] .= ' dimmed_text';
                    }

                    $singleview = '';

                    // FIXME: MDL-52678 This is extremely hacky we should have an API for inserting grade column links.
                    if (get_capability_info('gradereport/singleview:view')) {
                        if (has_all_capabilities(array('gradereport/singleview:view', 'moodle/grade:viewall',
                            'moodle/grade:edit'), $this->context)) {

                            $url = new moodle_url('/grade/report/singleview/index.php', array(
                                'id' => $this->course->id,
                                'item' => 'grade',
                                'itemid' => $element['object']->id));
                            $singleview = $OUTPUT->action_icon(
                                $url,
                                new pix_icon('t/editstring', get_string('singleview', 'grades', $element['object']->get_name()))
                            );
                        }
                    }

                    $itemcell->colspan = $colspan;
                    $itemcell->text = $headerlink . $arrow . $singleview;
                    $itemcell->header = true;
                    $itemcell->scope = 'col';

                    $headingrow->cells[] = $itemcell;
                }
            }
            $rows[] = $headingrow;
        }

        $rows = $this->get_right_icons_row($rows);

        // Preload scale objects for items with a scaleid and initialize tab indices
        $scaleslist = array();
        $tabindices = array();

        foreach ($this->gtree->get_items() as $itemid => $item) {
            $scale = null;
            if (!empty($item->scaleid)) {
                $scaleslist[] = $item->scaleid;
                $jsarguments['items'][$itemid] = array('id'=>$itemid, 'name'=>$item->get_name(true), 'type'=>'scale', 'scale'=>$item->scaleid, 'decimals'=>$item->get_decimals());
            } else {
                $jsarguments['items'][$itemid] = array('id'=>$itemid, 'name'=>$item->get_name(true), 'type'=>'value', 'scale'=>false, 'decimals'=>$item->get_decimals());
            }
            $tabindices[$item->id]['grade'] = $gradetabindex;
            $tabindices[$item->id]['feedback'] = $gradetabindex + $numusers;
            $gradetabindex += $numusers * 2;
        }
        $scalesarray = array();

        if (!empty($scaleslist)) {
            $scalesarray = $DB->get_records_list('scale', 'id', $scaleslist);
        }
        $jsscales = $scalesarray;

        // Get all the grade items if the user can not view hidden grade items.
        // It is possible that the user is simply viewing the 'Course total' by switching to the 'Aggregates only' view
        // and that this user does not have the ability to view hidden items. In this case we still need to pass all the
        // grade items (in case one has been hidden) as the course total shown needs to be adjusted for this particular
        // user.
        if (!$this->canviewhidden) {
            $allgradeitems = $this->get_allgradeitems();
        }

        foreach ($this->users as $userid => $user) {

            if ($this->canviewhidden) {
                $altered = array();
                $unknown = array();
            } else {
                $usergrades = $this->allgrades[$userid];
                $hidingaffected = grade_grade::get_hiding_affected($usergrades, $allgradeitems);
                $altered = $hidingaffected['altered'];
                $unknown = $hidingaffected['unknowngrades'];
                unset($hidingaffected);
            }

            $itemrow = new html_table_row();
            $itemrow->id = 'user_'.$userid;

            $fullname = fullname($user, $viewfullnames);
            $jsarguments['users'][$userid] = $fullname;

            foreach ($this->gtree->items as $itemid => $unused) {
                $item =& $this->gtree->items[$itemid];
                $grade = $this->grades[$userid][$item->id];

                $itemcell = new html_table_cell();

                $itemcell->id = 'u'.$userid.'i'.$itemid;
                $itemcell->attributes['data-itemid'] = $itemid;

                // Get the decimal points preference for this item
                $decimalpoints = $item->get_decimals();

                if (array_key_exists($itemid, $unknown)) {
                    $gradeval = null;
                } else if (array_key_exists($itemid, $altered)) {
                    $gradeval = $altered[$itemid];
                } else {
                    $gradeval = $grade->finalgrade;
                }
                if (!empty($grade->finalgrade)) {
                    $gradevalforjs = null;
                    if ($item->scaleid && !empty($scalesarray[$item->scaleid])) {
                        $gradevalforjs = (int)$gradeval;
                    } else {
                        $gradevalforjs = format_float($gradeval, $decimalpoints);
                    }
                    $jsarguments['grades'][] = array('user'=>$userid, 'item'=>$itemid, 'grade'=>$gradevalforjs);
                }

                // MDL-11274
                // Hide grades in the grader report if the current grader doesn't have 'moodle/grade:viewhidden'
                if (!$this->canviewhidden and $grade->is_hidden()) {
                    if (!empty($CFG->grade_hiddenasdate) and $grade->get_datesubmitted() and !$item->is_category_item() and !$item->is_course_item()) {
                        // the problem here is that we do not have the time when grade value was modified, 'timemodified' is general modification date for grade_grades records
                        $itemcell->text = "<span class='datesubmitted'>" .
                            userdate($grade->get_datesubmitted(), $strftimedatetimeshort) . "</span>";
                    } else {
                        $itemcell->text = '-';
                    }
                    $itemrow->cells[] = $itemcell;
                    continue;
                }

                // emulate grade element
                $eid = $this->gtree->get_grade_eid($grade);
                $element = array('eid'=>$eid, 'object'=>$grade, 'type'=>'grade');

                $itemcell->attributes['class'] .= ' grade i'.$itemid;
                if ($item->is_category_item()) {
                    $itemcell->attributes['class'] .= ' cat';
                }
                if ($item->is_course_item()) {
                    $itemcell->attributes['class'] .= ' course';
                }
                if ($grade->is_overridden()) {
                    $itemcell->attributes['class'] .= ' overridden';
                    $itemcell->attributes['aria-label'] = get_string('overriddengrade', 'gradereport_grader');
                }

                if (!empty($grade->feedback)) {
                    $feedback = wordwrap(trim(format_string($grade->feedback, $grade->feedbackformat)), 34, '<br>');
                    $itemcell->attributes['data-feedback'] = $feedback;
                    $jsarguments['feedback'][] = array('user'=>$userid, 'item'=>$itemid, 'content' => $feedback);
                }

                if ($grade->is_excluded()) {
                    // Adding white spaces before and after to prevent a screenreader from
                    // thinking that the words are attached to the next/previous <span> or text.
                    $itemcell->text .= " <span class='excludedfloater'>" . $strexcludedgrades . "</span> ";
                }

                // Do not show any icons if no grade (no record in DB to match)
                if (!$item->needsupdate and $USER->gradeediting[$this->courseid]) {
                    $itemcell->text .= $this->get_icons($element);
                }

                $hidden = '';
                if ($grade->is_hidden()) {
                    $hidden = ' dimmed_text ';
                }

                $gradepass = ' gradefail ';
                if ($grade->is_passed($item)) {
                    $gradepass = ' gradepass ';
                } else if (is_null($grade->is_passed($item))) {
                    $gradepass = '';
                }

                // if in editing mode, we need to print either a text box
                // or a drop down (for scales)
                // grades in item of type grade category or course are not directly editable
                if ($item->needsupdate) {
                    $itemcell->text .= "<span class='gradingerror{$hidden}'>" . $strerror . "</span>";

                } else if ($USER->gradeediting[$this->courseid]) {

                    if ($item->scaleid && !empty($scalesarray[$item->scaleid])) {
                        $itemcell->attributes['class'] .= ' grade_type_scale';
                    } else if ($item->gradetype == GRADE_TYPE_VALUE) {
                        $itemcell->attributes['class'] .= ' grade_type_value';
                    } else if ($item->gradetype == GRADE_TYPE_TEXT) {
                        $itemcell->attributes['class'] .= ' grade_type_text';
                    }

                    if ($item->scaleid && !empty($scalesarray[$item->scaleid])) {
                        $scale = $scalesarray[$item->scaleid];
                        $gradeval = (int)$gradeval; // scales use only integers
                        $scales = explode(",", $scale->scale);
                        // reindex because scale is off 1

                        // MDL-12104 some previous scales might have taken up part of the array
                        // so this needs to be reset
                        $scaleopt = array();
                        $i = 0;
                        foreach ($scales as $scaleoption) {
                            $i++;
                            $scaleopt[$i] = $scaleoption;
                        }

                        if ($quickgrading and $grade->is_editable()) {
                            $oldval = empty($gradeval) ? -1 : $gradeval;
                            if (empty($item->outcomeid)) {
                                $nogradestr = $this->get_lang_string('nograde');
                            } else {
                                $nogradestr = $this->get_lang_string('nooutcome', 'grades');
                            }
                            $attributes = array('tabindex' => $tabindices[$item->id]['grade'], 'id'=>'grade_'.$userid.'_'.$item->id);
                            $gradelabel = $fullname . ' ' . $item->get_name(true);
                            $itemcell->text .= html_writer::label(
                                get_string('useractivitygrade', 'gradereport_grader', $gradelabel), $attributes['id'], false,
                                array('class' => 'accesshide'));
                            $itemcell->text .= html_writer::select($scaleopt, 'grade['.$userid.']['.$item->id.']', $gradeval, array(-1=>$nogradestr), $attributes);
                        } else if (!empty($scale)) {
                            $scales = explode(",", $scale->scale);

                            // invalid grade if gradeval < 1
                            if ($gradeval < 1) {
                                $itemcell->text .= "<span class='gradevalue{$hidden}{$gradepass}'>-</span>";
                            } else {
                                $gradeval = $grade->grade_item->bounded_grade($gradeval); //just in case somebody changes scale
                                $itemcell->text .= "<span class='gradevalue{$hidden}{$gradepass}'>{$scales[$gradeval - 1]}</span>";
                            }
                        }

                    } else if ($item->gradetype != GRADE_TYPE_TEXT) { // Value type
                        if ($quickgrading and $grade->is_editable()) {
                            $value = format_float($gradeval, $decimalpoints);
                            $gradelabel = $fullname . ' ' . $item->get_name(true);
                            $itemcell->text .= '<label class="accesshide" for="grade_'.$userid.'_'.$item->id.'">'
                                .get_string('useractivitygrade', 'gradereport_grader', $gradelabel).'</label>';
                            $itemcell->text .= '<input size="6" tabindex="' . $tabindices[$item->id]['grade']
                                . '" type="text" class="text" title="'. $strgrade .'" name="grade['
                                .$userid.'][' .$item->id.']" id="grade_'.$userid.'_'.$item->id.'" value="'.$value.'" />';
                        } else {
                            $itemcell->text .= "<span class='gradevalue{$hidden}{$gradepass}'>" .
                                format_float($gradeval, $decimalpoints) . "</span>";
                        }
                    }

                    // If quickfeedback is on, print an input element
                    if ($showquickfeedback and $grade->is_editable()) {
                        $feedbacklabel = $fullname . ' ' . $item->get_name(true);
                        $itemcell->text .= '<label class="accesshide" for="feedback_'.$userid.'_'.$item->id.'">'
                            .get_string('useractivityfeedback', 'gradereport_grader', $feedbacklabel).'</label>';
                        $itemcell->text .= '<input class="quickfeedback" tabindex="' . $tabindices[$item->id]['feedback'].'" id="feedback_'.$userid.'_'.$item->id
                            . '" size="6" title="' . $strfeedback . '" type="text" name="feedback['.$userid.']['.$item->id.']" value="' . s($grade->feedback) . '" />';
                    }

                } else { // Not editing
                    $gradedisplaytype = $item->get_displaytype();

                    if ($item->scaleid && !empty($scalesarray[$item->scaleid])) {
                        $itemcell->attributes['class'] .= ' grade_type_scale';
                    } else if ($item->gradetype == GRADE_TYPE_VALUE) {
                        $itemcell->attributes['class'] .= ' grade_type_value';
                    } else if ($item->gradetype == GRADE_TYPE_TEXT) {
                        $itemcell->attributes['class'] .= ' grade_type_text';
                    }

                    // Only allow edting if the grade is editable (not locked, not in a unoverridable category, etc).
                    if ($enableajax && $grade->is_editable()) {
                        // If a grade item is type text, and we don't have show quick feedback on, it can't be edited.
                        if ($item->gradetype != GRADE_TYPE_TEXT || $showquickfeedback) {
                            $itemcell->attributes['class'] .= ' clickable';
                        }
                    }

                    if ($item->needsupdate) {
                        $itemcell->text .= "<span class='gradingerror{$hidden}{$gradepass}'>" . $error . "</span>";
                    } else {
                        // The max and min for an aggregation may be different to the grade_item.
                        if (!is_null($gradeval)) {
                            $item->grademax = $grade->get_grade_max();
                            $item->grademin = $grade->get_grade_min();
                        }

                        $itemcell->text .= "<span class='gradevalue{$hidden}{$gradepass}'>" .
                            grade_format_gradevalue($gradeval, $item, true, $gradedisplaytype, null) . "</span>";
                        if ($showanalysisicon) {
                            $itemcell->text .= $this->gtree->get_grade_analysis_icon($grade);
                        }
                    }
                }

                // Enable keyboard navigation if the grade is editable (not locked, not in a unoverridable category, etc).
                if ($enableajax && $grade->is_editable()) {
                    // If a grade item is type text, and we don't have show quick feedback on, it can't be edited.
                    if ($item->gradetype != GRADE_TYPE_TEXT || $showquickfeedback) {
                        $itemcell->attributes['class'] .= ' gbnavigable';
                    }
                }

                if (!empty($this->gradeserror[$item->id][$userid])) {
                    $itemcell->text .= $this->gradeserror[$item->id][$userid];
                }

                $itemrow->cells[] = $itemcell;
            }
            $rows[] = $itemrow;
        }

        if ($enableajax) {
            $jsarguments['cfg']['ajaxenabled'] = true;
            $jsarguments['cfg']['scales'] = array();
            foreach ($jsscales as $scale) {
                // Trim the scale values, as they may have a space that is ommitted from values later.
                $jsarguments['cfg']['scales'][$scale->id] = array_map('trim', explode(',', $scale->scale));
            }
            $jsarguments['cfg']['feedbacktrunclength'] =  $this->feedback_trunc_length;

            // Student grades and feedback are already at $jsarguments['feedback'] and $jsarguments['grades']
        }
        $jsarguments['cfg']['isediting'] = (bool)$USER->gradeediting[$this->courseid];
        $jsarguments['cfg']['courseid'] = $this->courseid;
        $jsarguments['cfg']['studentsperpage'] = $this->get_students_per_page();
        $jsarguments['cfg']['showquickfeedback'] = (bool) $showquickfeedback;

        $module = array(
            'name'      => 'gradereport_grader',
            'fullpath'  => '/grade/report/grader/module.js',
            'requires'  => array('base', 'dom', 'event', 'event-mouseenter', 'event-key', 'io-queue', 'json-parse', 'overlay')
        );
        $PAGE->requires->js_init_call('M.gradereport_grader.init_report', $jsarguments, false, $module);
        $PAGE->requires->strings_for_js(array('addfeedback', 'feedback', 'grade'), 'grades');
        $PAGE->requires->strings_for_js(array('ajaxchoosescale', 'ajaxclicktoclose', 'ajaxerror', 'ajaxfailedupdate', 'ajaxfieldchanged'), 'gradereport_grader');
        if (!$enableajax && $USER->gradeediting[$this->courseid]) {
            $PAGE->requires->yui_module('moodle-core-formchangechecker',
                'M.core_formchangechecker.init',
                array(array(
                    'formid' => 'gradereport_grader'
                ))
            );
            $PAGE->requires->string_for_js('changesmadereallygoaway', 'moodle');
        }

        $rows = $this->get_right_range_row($rows);
        if ($displayaverages) {
            $rows = $this->get_right_avg_row($rows, true);
            $rows = $this->get_right_avg_row($rows);
        }

        return $rows;
    }
    

    public static function append_percentages_to_item_categories(&$rigth_rows, $courseid) {
        $indexes_of_category_item_names= custom_grade_report_grader::get_item_category_title_row_indexes($rigth_rows);
        $doc = new DOMDocument("1.0", 'utf-8');
        if ( $indexes_of_category_item_names ) {

            foreach( $indexes_of_category_item_names as $index_of_category_item_names ) {
                /* @var html_table_row $category_item_row */
                $category_item_row = $rigth_rows[$index_of_category_item_names];
                $category_item_cells = $category_item_row->cells;

                /* @var html_table_cell $category_item_cell */
                foreach ($category_item_cells as $category_item_cell) {

                    if (!is_null($category_item_cell->text) && $category_item_cell->text !== '') {
                        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $category_item_cell->text);
                        $span_title = $doc->getElementsByTagName('span');
                        if ($span_title->item(0)) {
                            $category_span = $span_title->item(0);
                            $category_name = $category_span->getAttribute('title');
                            $category = grade_category::fetch(array('courseid' => $courseid, 'fullname' => $category_name));
                            $category_parent = $category->get_parent_category();
                            if ($category_parent->aggregation == 10) { // if agregation is Weighted mean of grades
                                $category_agregationcoef = $category->get_grade_item()->aggregationcoef;
                                $agregationcoef = number_format((float)$category_agregationcoef, 2, '.', '');
                                $category_span->textContent .= " ($agregationcoef%)";
                                $category_item_cell->text = $doc->saveHTML();
                            }

                        }

                    }
                }
            }
        }
    }

    public static function append_percentages_to_items(&$rigth_rows) {
        $index_of_item_names = custom_grade_report_grader::get_items_title_row_index($rigth_rows);
        //If exists a row with item names
        if ($index_of_item_names !== false ) {
            /* @var html_table_row $item_name_row */
            $item_name_row = $rigth_rows[$index_of_item_names];
            $item_name_cells = $item_name_row->cells;
            /* @var html_table_cell $item_name_cell */

            foreach ($item_name_cells as $item_name_cell) {
                $item_id = $item_name_cell->attributes['data-itemid'];
                $item = grade_item::fetch(array('id' => $item_id));
                /* @var $item_parent_category grade_category */
                $item_parent_category = $item->get_parent_category();
                if (
                    $item->itemtype !== 'course' && /* Total course grade, always is 0.0% */
                    $item_parent_category->aggregation == 10) { // if agregation is Weighted mean of grades
                    $agregationcoef = number_format((float)$item->aggregationcoef, 2, '.', '');
                    $item_name_cell->text .= " ($agregationcoef%)";
                }
            }
        }
    }
    public function get_right_rows($displayaverages) {

        $rigth_rows = $this->custom_get_right_rows($displayaverages); // TODO: Change the autogenerated stub
        custom_grade_report_grader::append_percentages_to_item_categories($rigth_rows, $this->courseid);
        custom_grade_report_grader::append_percentages_to_items($rigth_rows);
        return $rigth_rows;
    }


    /**
     * Return the index of the row than contains the items name
     *
     * In a set of rows of the grader element, the item rows always the cells have in 'attributes', 'data-itemiid' property
     *
     *
     * # Example
     * This is a cell than contains a grade item name:
     *```
     * [0] => html_table_cell Object
     *   (
     *   [id] =>
     *   [text] =>nota de prueba
     *   [abbr] =>
     *   [colspan] => 1
     *   [rowspan] =>
     *   [scope] => col
     *   [header] => 1
     *   [style] =>
     *   [attributes] => Array
     *   (
     *   [class] => item catlevel1 highlightable i84313
     *   [data-itemid] => 84313
     *   )
     *   )
     *```
     * @see html_table_cell
     * @see html_table_row
     * @param $rows
     * @return false|int If does not exist the row with the item names, return false, return its index otherwise
     */
    public static function get_items_title_row_index($rows) {
        /* @var html_table_row $row */
        foreach($rows as $key => $row) {
            /* @var html_table_cell $cell */
            foreach($row->cells as $cell) {
                if(isset($cell->attributes) && isset($cell->attributes['data-itemid'])) {
                    return $key;
                }
            }
        }
        return false;
    }

    /**
     * Return the indexes of the row than contains the item categories names, can be exist diferent
     * levels of grade categories, each level is a row
     *
     * In a set of rows of the grader element, the category item rows, always
     * the cells have in 'attributes', a class named 'category'
     *
     * @param $rows
     * @return bool|array(int)
     */
    public static function get_item_category_title_row_indexes($rows) {
        $category_title_indexes = array();
        /* @var html_table_row $row */
        foreach($rows as $key => $row) {
            /* @var html_table_cell $cell */
            foreach($row->cells as $cell) {
                if(
                    $key !== 0 && //Always, the first grade category is the course name and cotains all others
                    isset($cell->attributes) &&
                    isset($cell->attributes['class']) &&
                    /* Should contain a class 'category', not 'categoryitem' or other, exactly 'category'*/
                    preg_match('#\\bcategory\\b#', $cell->attributes['class']) === 1)  {
                    $category_title_indexes[$key] = true;
                }
            }
        }
        if(count($category_title_indexes) > 0) {
            return array_keys($category_title_indexes);
        }
        return false;
    }
}