<?php
require_once '../../config.php';
require_once (__DIR__ .'/managers/grader_lib.php');

//echo '<pre>';
//$data = get_normalized_all_grade_info(25643);
//print_r(json_encode($data->grades[0]));
//die;
$PAGE->requires->js('/local/customgrader/js/gridViewScroll.js', true);
$PAGE->requires->js('/local/customgrader/js/rxjs.umd.js', true);
$PAGE->requires->js('/local/customgrader/js/vue-rx.js', true);
$PAGE->requires->jquery();
$courseid        = required_param('id', PARAM_INT);
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->css('/local/customgrader/style/vue-js-modal.css');
$PAGE->requires->css('/local/customgrader/style/font-awesome.css');
$PAGE->requires->css('/local/customgrader/style/vue-flex.css', true);
$PAGE->requires->css('/local/customgrader/style/vue-toasted.css', true);
$PAGE->requires->css('/local/customgrader/style/styles_grader.css', true);
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_customgrader/grader.vue', null);

$PAGE->requires->js_call_amd('local_customgrader/grader', 'init');
echo $OUTPUT->footer();
