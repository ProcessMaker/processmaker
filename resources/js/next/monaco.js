import MonacoEditor from "vue-monaco";
// import { getGlobalVariable, setGlobalVariable } from "./globalVariables";

// const Vue = getGlobalVariable("Vue");

// Vue.component("MonacoEditor", MonacoEditor);
// setGlobalVariable("VueMonaco", MonacoEditor);
// setGlobalVariable("monaco", MonacoEditor);

export default {
  global: {
    monaco: MonacoEditor,
    VueMonaco: MonacoEditor,
  },
  components: {
    MonacoEditor,
  },
};
