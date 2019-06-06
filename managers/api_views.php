<?php
require_once (__DIR__ . '/../classes/API/BaseAPIView.php');
require_once (__DIR__ . '/../managers/grader_lib.php');
require_once (__DIR__ . '/../classes/Errors/CustomError.php');

class GetNormalizedGraderData extends BaseAPIView {
    public function send_response() {

        $data = get_normalized_all_grade_info($this->args['courseid']);
        return $data;
    }
}


/**
 * Class UpdateElement
 * Update category
 */
Class UpdateCategory extends BaseAPIView {
    public function get_required_data(): array {
        return [
            'category', // type of grade_category
        ];
    }
    public function send_response() {
        /** @var  $category grade_category */
        $category = $this->data['category'];
        $editedCategoryResponse = editCategory($category);

        $levels = get_table_levels($category->courseid);
        $response = [
            'category' => $editedCategoryResponse,
            'levels' => $levels
        ];
        return $response;
    }
}
/**
 * Class UpdateElement
 * Update category
 */
Class UpdateItem extends BaseAPIView {
    public function get_required_data(): array {
        return [
            'item', // type of grade_category
        ];
    }

    public function send_response() {
        /** @var  $item grade_item */
        $item = $this->data['item'];
        $editedItemResponse =  editItem($item);
        update_grade_items_by_course( $this->data['courseid']);
        $levels = get_table_levels($item->courseid);

        $response = [
            'item' => $editedItemResponse,
            'levels' => $levels,
            'other_grades'=> get_student_grades($editedItemResponse->courseid)
            ];
        return $response;
    }
}


/**
 * Class Add required item
 * partial_exam should be an object with this properties:
 * - courseid
 * - parent_category
 * - itemname
 * - aggregationcoef
 * - aggregation
 */
class AddPartialExam extends BaseAPIView {
    public function get_required_data(): array {
        return ['partial_exam'];
    }

    /**
     * @return array|false
     * @throws dml_exception
     */
    public function send_response() {
        $partial_exam = $this->data['partial_exam'];
        $insert_response = insertParcial(
            $partial_exam->courseid,
            $partial_exam->parent_category,
            $partial_exam->itemname,
            $partial_exam->aggregation,
            $partial_exam->aggregationcoef );
        if ($insert_response !== false) {
            $levels = get_table_levels($partial_exam->courseid);
            $response = [
                'levels'=>$levels,
            ];
            return array_merge($response, $insert_response);
        } else {
            $this->add_error(new CustomError(400, 'Ha ocurrido un error inesperado al guardar el item'));
            return false;
        }
    }
}

/**
 * Class AddItem
 * Required item properties are:
 * - courseid
 * - parent_category
 * - itemname
 * - aggregationcoef
 */
class AddItem extends BaseAPIView {
    public function get_required_data(): array {
        return ['item'];
    }

    /**
     * @return array|false
     * @throws dml_exception
     */
    public function send_response() {
        /** @var  $item grade_item */
        $item = $this->data['item'];
        $item_or_false = insertItem($item->courseid, $item->parent_category, $item->itemname, $item->aggregationcoef );
        if ($item_or_false !== false) {
            $levels = get_table_levels($item->courseid);
            $response = [
                'levels'=>$levels,
                'item'=>$item_or_false,
            ];
            return $response;
        } else {
            $this->add_error(new CustomError(400, 'Ha ocurrido un error inesperado al guardar el item'));
            return false;
        }
    }
}

/**
 * Class Category
 * Required item properties are:
 * - courseid
 * - parent_category
 * - itemname
 * - aggregationcoef
 */
class AddCategory extends BaseAPIView {
    public function get_required_data(): array {
        return ['category', 'weight'];
    }
    public function send_response() {
        /** @var  $category grade_category */
        $category = $this->data['category'];
        $weight = $this->data['weight'];
        $cat_creation_response = insertCategory(
            $category->courseid,
            $category->parent_category,
            $category->fullname,
            $category->aggregation,
            $weight);
        if ($cat_creation_response !== false) {
            $category = $cat_creation_response['category'];
            $item = $cat_creation_response['category_item'];
        }
        $levels = get_table_levels($category->courseid);
        $response = [
            'levels'=>$levels,
            'category_item'=>$item,
            'category'=>$category,
        ];
        return $response;
    }
}

class DeleteItem extends BaseAPIView {
    public function send_response() {
        $item_id = $this->args['item_id'];
        $item = grade_item::fetch(array('id'=>$item_id));
        $course_id =  $item->courseid;
        $deleted = delete_item($item_id, $course_id);
        $levels = get_table_levels($course_id);
        $response = [
            'levels' => $levels
        ];
        return $response;
    }
}

class DeleteCategory extends BaseAPIView {
    public function send_response() {
        $category_id = $this->args['category_id'];

        $category = grade_category::fetch(array('id'=>$category_id));
        $course_id= $category->courseid;
        $deleted = delete_category($category_id, $course_id);

        $levels = get_table_levels($course_id);
        $response = [
            'levels' => $levels
        ];
        return $response;
    }
}

class UpdateGrade extends BaseAPIView {
    public function get_required_data(): array {
        return [
            'courseid',
            'itemid',
            'userid',
            'finalgrade'
        ];
    }
    public function send_response() {

        $userid = $this->data['userid'];
        $itemid =  $this->data['itemid'];
        update_grade_items_by_course( $this->data['courseid']);

        if(!update_grades_moodle_($userid ,$itemid, $this->data['finalgrade'], $this->data['courseid'])) {
            $this->add_error(new CustomError(-1, 'No se ha podido actualizar la nota'));
            return false;
        } else {
            return array(
                'grade'=> grade_grade::fetch(array('userid'=>$userid , 'itemid'=>$itemid)),
                'other_grades'=> get_student_grades($this->data['courseid'], null, $userid)
            );
        }

    }
}