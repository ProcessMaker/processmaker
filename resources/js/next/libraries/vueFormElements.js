import { setGlobalVariable } from "../globalVariables";

import("@processmaker/vue-form-elements").then((vueforms) => {
  setGlobalVariable("VueFormElements", vueforms);
});
