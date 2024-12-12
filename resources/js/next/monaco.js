import MonacoEditor from "vue-monaco";
import { getGlobalVariable, setGlobalVariable } from "./globalVariables";

const Vue = getGlobalVariable("Vue");

Vue.component("MonacoEditor", MonacoEditor);
setGlobalVariable("VueMonaco", MonacoEditor);
setGlobalVariable("monaco", MonacoEditor);

// const Vue = getGlobalVariable("Vue");

// Vue.component("MonacoEditor", (resolve, reject) => {
//   console.log("LOADER MONACO %%%%%%%%%%");

//   import("vue-monaco").then((MonacoEditor) => {
//     setGlobalVariable("VueMonaco", MonacoEditor.default);
//     resolve(MonacoEditor.default);
//   }).catch(reject);
// });
