import VueCookies from "vue-cookies";
import { getGlobalVariable } from "../globalVariables";

const Vue = getGlobalVariable("Vue");

Vue.use(VueCookies);
