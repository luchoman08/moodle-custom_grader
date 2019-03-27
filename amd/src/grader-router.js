define([
    'local_customgrader/grader-component-main',
    'local_customgrader/vendor-vue'
], function(g_c_main, Vue) {
    var Grader = Vue.component(g_c_main.name, g_c_main.component);
    var routes = [
        {path: '/grader', component: Grader},
        {path: '/bar'}
    ];
    return {
        routes: routes
    };
});