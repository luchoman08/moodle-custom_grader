/**
 *
 */

define([], function() {
    var aggregations = {
        SIMPLE: 1,
        PROMEDIO: 10
    };
    var sortStudentMethods = {
        NAME: 'name',
        LAST_NAME: 'lastname'
    };
    return {
        aggregations: aggregations,
        sortStudentMethods: sortStudentMethods
    };
});