import Vuex from "vuex";
import GlobalStore from "../../globalStore";
import { getGlobalVariable } from "../globalVariables";

const Vue = getGlobalVariable("Vue");

Vue.use(Vuex);
Vue.use(GlobalStore);
