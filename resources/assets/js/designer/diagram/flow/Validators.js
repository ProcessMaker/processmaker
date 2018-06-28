// This object contains the validations of every BPMN element
import _ from "lodash"

export const Validators = {
    all: ["Pool", "Lane"],
    verifyConnectWith: (source, target) => {
        let res = Validators.all
        if (source && this[source]) {
            res = _.concat(res, this[source])
        }
        return target && res.indexOf(target) >= 0 ? false : true
    }
}