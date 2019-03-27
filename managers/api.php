<?php

require_once(__DIR__ . '/../classes/API/BaseAPI.php');
require_once(__DIR__ . '/api_views.php');
$api = new BaseAPI();
$api->get('get_normalized_grade_data/:courseid', GetNormalizedGraderData::class);
$api->post('update_grade', UpdateGrade::class);
$api->post('update_item', UpdateItem::class);
$api->post('update_category', UpdateCategory::class);
$api->delete(':course_id/item/:item_id', DeleteItem::class);
$api->delete(':course_id/item/:category', DeleteCategory::class);
$api->run();
