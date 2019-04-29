<?php
require_once '../../config.php';
require_once (__DIR__ .'/managers/grader_lib.php');
$courseid        = required_param('id', PARAM_INT);
$PAGE->requires->jquery();

$url = new moodle_url('/local/customgrader/index.php', array('id' => $courseid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
/// Make sure they can even access this course
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}
require_login($course);

$context = context_course::instance($course->id);
require_capability('moodle/grade:manage', $context);
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->css('/local/customgrader/style/vue-js-modal.css');
// Moove have his own font awesome, if is required hear all icons crash
if(!$PAGE->theme->name === 'moove') {
    $PAGE->requires->css('/local/customgrader/style/font-awesome.css');
}

$PAGE->requires->css('/local/customgrader/style/vue-flex.css', true);
$PAGE->requires->css('/local/customgrader/style/vue-toasted.css', true);
$PAGE->requires->css('/local/customgrader/style/styles_grader.css', true);
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_customgrader/grader.vue', null);

$PAGE->requires->js_call_amd('local_customgrader/grader', 'init');
echo $OUTPUT->footer();
