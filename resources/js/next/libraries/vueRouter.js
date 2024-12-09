import Router from "vue-router";

import { setGlobalVariable, getGlobalVariable, setGlobalPMVariable } from "../globalVariables";

const Vue = getGlobalVariable("Vue");

setGlobalVariable("VueRouter", Router);
setGlobalPMVariable("Router", new Router({
  mode: "history",
}));

Vue.use(Router);
