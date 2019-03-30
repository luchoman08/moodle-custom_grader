<?php
namespace local_customgrader;
class custom_grade_category extends \grade_category {
    /**
     * @var number $grade_item Associated grade item id
     */
    public $grade_item;

    private static function append_grade_item(\grade_category $grade_category) {
        $_grade_category = (object) $grade_category;
        $item = $grade_category->get_grade_item();
        $_grade_category->grade_item = $item->id;
        return $_grade_category;
    }
    /**
     * Finds and returns a grade_category instance based on params.
     *
     * @param array $params associative arrays varname=>value
     * @return custom_grade_category The retrieved grade_category instance or false if none found.
     */
    public static function fetch($params) {
        $category = parent::fetch($params);
        $custom_category = custom_grade_category::append_grade_item($category);
        return $custom_category;
    }

    /**
     * Return all categories by params
     * @param array $params
     * @return array
     */
    public static function fetch_all($params)
    {
        $categories = parent::fetch_all($params);
        $custom_grade_categories = array_map(function($c){return self::append_grade_item($c);}, $categories);
        return $custom_grade_categories;
    }
}