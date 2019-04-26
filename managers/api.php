<?php

require_once(__DIR__ . '/../classes/API/BaseAPI.php');
require_once(__DIR__ . '/api_views.php');
$api = new BaseAPI();
$api->get('/grader/:courseid', GetNormalizedGraderData::class);
$api->put('/grade', UpdateGrade::class);
$api->put('/category', UpdateCategory::class);
$api->put('/item', UpdateItem::class);
$api->post('/category', AddCategory::class);
$api->post('/item', AddItem::class);
$api->post('/partial_exam', AddPartialExam::class);
$api->delete('/item/:item_id', DeleteItem::class);
$api->delete('/category/:category_id', DeleteCategory::class);
$api->run();
