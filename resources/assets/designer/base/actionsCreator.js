import _ from "lodash";
/**
 * Create Actions based in a map object
 * @param actionMap
 * @returns {{}}
 */
export default function actionsCreator(actionMap) {
    let actionNeo = {};
    _.map(actionMap, (value, key, object) => {
        mappingValues(value, key, object, actionNeo, key);
    });
    return actionNeo;
}

/**
 * This method is recursive to map all object
 * @param value
 * @param key
 * @param object
 * @param nObject
 * @param pwd
 */
function mappingValues(value, key, object, nObject, pwd) {
    if (_.isFunction(value)) {
        creationObject(pwd, value, nObject);
    } else {
        _.map(value, (val, k, obj) => {
            mappingValues(val, k, obj, nObject, `${pwd}/${k}`);
        });
    }
}

/**
 * This method create a object based in map of actions {type: "action", payload: some...}
 * @param pwd
 * @param value
 * @param nObject
 * @returns {*}
 */
function creationObject(pwd, value, nObject) {
    let nObjectF = nObject;
    let root = _.split(pwd, "/");
    let first;
    while (root.length > 0) {
        first = _.head(root);
        if (!nObjectF[first]) {
            nObjectF[first] = {};
        }
        root = _.drop(root);
        if (root.length === 0) {
            nObjectF[first] = function (payload) {
                return {
                    type: pwd,
                    payload: value(payload)
                };
            };
        }
        nObjectF = nObjectF[first];
    }
    return nObject;
}