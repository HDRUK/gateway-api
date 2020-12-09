//takes either a String or an array of Strings and removes non-breaking spaces
const removeNonBreakingSpaces = (str) => {
    let sanitizedValues = [];

    if(Array.isArray(str) && str !== []){
        str.forEach((s) => {sanitizedValues.push(removeNonBreakingSpaces(s))});
    }
    else if(!Array.isArray(str) && typeof(str) !== 'object'){
        var re = /&nbsp;/g
        return (!str || !isNaN(str)) ? str : str.replace(re ," ");
    }
    return sanitizedValues;
}

module.exports = {
    removeNonBreakingSpaces: removeNonBreakingSpaces
}