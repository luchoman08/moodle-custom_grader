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
        $levels = get_table_levels($item->courseid);
        $response = [
            'item' => $editedItemResponse,
            'levels' => $levels
            ];
        return $response;
    }
}

class DeleteItem extends BaseAPIView {
    public function send_response() {
        $item_id = $this->args['item_id'];
        $course_id= $this->args['course_id'];
        $deleted = delete_item($item_id, $course_id);
        $levels = get_table_levels($course_id);
        $response = [
            'levels' => $levels
        ];
        return $response;
    }
}

class DeleteCategory extends BaseAPIView {
    public function get_required_data(): array {
        return [
            'categoryId',
            'courseId'
        ];
    }
    public function send_response() {
        $category_id = $this->data['categoryId'];
        $course_id= $this->data['courseId'];
        $deleted = delete_item($category_id, $course_id);
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
        if(!update_grades_moodle_($userid ,$itemid, $this->data['finalgrade'], $this->data['courseid'])) {
            $this->add_error(new CustomError(-1, 'No se ha podido actualizar la nota'));
            return false;
        } else {
            return grade_grade::fetch(array('userid'=>$userid , 'itemid'=>$itemid));
        }

    }
}