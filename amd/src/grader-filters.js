define([], function() {
    var round_funct = function(value, accuracy, keep) {
        if (typeof value !== 'string' && typeof value !== 'number') {
            return value;
        }
        if (typeof value === 'string') {
            value = Number(value);
        }
        var fixed = value.toFixed(accuracy);
        return keep ? fixed : +fixed;
    };
    var round_name = 'round';
    return {
        round: {name: round_name, func: round_funct}
    };
});