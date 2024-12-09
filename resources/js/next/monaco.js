import MonacoEditor from "vue-monaco";

import { getGlobalVariable, setGlobalVariable } from "../globalVariables";

const Vue = getGlobalVariable("Vue");

Vue.component("MonacoEditor", MonacoEditor);

setGlobalVariable("VueMonaco", MonacoEditor);
