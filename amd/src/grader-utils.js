define([], function () {
    var removeInsignificantTrailZeros = function(value) {
        if (typeof value === 'string') {
            return Number(Number(value).toString());
        }
        if (typeof value === 'number') {
            return value.toString();
        }
    };
    var getCourseId = function() {
        var informacionUrl = window.location.search.split("=");
        var curso = -1;
        for (var i = 0; i < informacionUrl.length; i += 2) {
            if (informacionUrl[i] === "?id") {
                curso = informacionUrl[i + 1];
            }
        }
        return curso;
    };
    return {
        removeInsignificantTrailZeros: removeInsignificantTrailZeros,
        getCourseId: getCourseId
    };
});