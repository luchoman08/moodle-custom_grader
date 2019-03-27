define([
    'local_customgrader/vendor-vue',
    'local_customgrader/grader-utils',
    'core/config',
], function (Vue, g_utils,  cfg) {
    var COURSE_ID = g_utils.getCourseId();
    var BASE_URL = `${cfg.wwwroot}/local/customgrader/managers/`;
    var api_service = {
        get: (resource) => {
            return Vue.http.get(`${BASE_URL}/api.php/${resource}`)
                .then (response => response.body)
                .catch(response => console.error(response))
        } ,
        delete: (resource) => {
            return Vue.http.delete(`${BASE_URL}/api.php/${resource}`)
                .then (response => response.body)
                .catch(response => console.error(response))
        },
        post: (resource, data) => {
            var data_ = {
                ...data,
                course: COURSE_ID
            };
            return Vue.http.post(`${BASE_URL}/api.php/${resource}`, data_)
                .then( response=> response.body )
                .catch(response => console.error(response));
        }
    };
   return {
       get_grader_data: (courseId) => {
           /*return new Promise(function(resolve, reject) {
              resolve(dummy_data);
           });*/
           return api_service.get(`get_normalized_grade_data/${courseId}`);
       },
       update_grade: (grade, courseId) => {
           var send_info = {...grade, courseid: courseId};
           return api_service.post('update_grade', send_info);
       },
       update_category: (category) => {
           var send_info = { category: category};
           return api_service.post('update_category', send_info);
       },
       update_item: (item) => {
           var send_info = { item: item };
           return api_service.post('update_item', send_info);
       },
       delete_item: (itemId, courseId) => {
           return api_service.delete(`${courseId}/item/${itemId}`);
       }
   };
});