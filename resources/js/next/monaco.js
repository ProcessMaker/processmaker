import MonacoEditor from "vue-monaco";
import { getGlobalVariable, setGlobalVariable } from "./globalVariables";

export default () => {
  const Vue = getGlobalVariable("Vue");
  Vue.component("MonacoEditor", MonacoEditor);
  setGlobalVariable("VueMonaco", MonacoEditor);
  setGlobalVariable("monaco", MonacoEditor);
};
