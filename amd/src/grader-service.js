define([
    'local_customgrader/vendor-vue',
    'local_customgrader/grader-utils',
    'core/config',
], function (Vue, g_utils,  cfg) {
    var COURSE_ID = g_utils.getCourseId();
    var BASE_URL = `${cfg.wwwroot}/local/customgrader/managers`;
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
            console.log(data_, 'data_ at api post');
            return Vue.http.post(`${BASE_URL}/api.php/${resource}`, data_)
                .then( response=> response.body )
                .catch(response => console.error(response));
        },
        put: (resource, data) => {
            var data_ = {
                ...data,
                course: COURSE_ID
            };
            console.log(data_, 'data_ at api put');
            return Vue.http.put(`${BASE_URL}/api.php/${resource}`, data_)
                .then( response=> response.body )
                .catch(response => console.error(response));
        }
    };
   return {
       get_grader_data: (courseId) => {
           return api_service.get(`grader/${courseId}`);
       },
       update_grade: (grade, courseId) => {
           var send_info = {...grade, courseid: courseId};
           return api_service.put('grade', send_info);
       },
       update_category: (category) => {
           var send_info = { category: category};
           console.log(send_info, 'send info at category update');
           return api_service.put('category', send_info);
       },
       add_category: (category) => {
           var send_info = { category: category};
           console.log(send_info, 'send info at item creation');
           return api_service.post('category', send_info);
       },
       add_item: (item) => {
           var send_info = { item: item };
           console.log(send_info, 'send info at item creation');
           return api_service.post('item', send_info);
       },
       update_item: (item) => {
           var send_info = { item: item };
           return api_service.put('item', send_info);
       },
       delete_item: (itemId) => {
           return api_service.delete(`/item/${itemId}`);
       },
       delete_category: (categoryId) => {
           return api_service.delete(`/category/${categoryId}`);
       }
   };
});