import VueFormElements from "@processmaker/vue-form-elements";
import { setGlobalVariable, getGlobalVariable } from "../globalVariables";

const Vue = getGlobalVariable("Vue");

Vue.use(VueFormElements);

setGlobalVariable("VueFormElements", VueFormElements);

// import("@processmaker/vue-form-elements").then((vueforms) => {
//   setGlobalVariable("VueFormElements", vueforms);
// });
