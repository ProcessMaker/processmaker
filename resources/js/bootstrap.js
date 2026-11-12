import "./bootstrap-core";
import MonacoEditor from "vue-monaco";
import ScreenSelect from "./processes/modeler/components/inspector/ScreenSelect.vue";

window.VueMonaco = require("vue-monaco");
window.Modeler = require("@processmaker/modeler");
window.ModelerInspector = require("./processes/modeler/components/inspector");

window.Vue.component("screen-select", ScreenSelect);
window.Vue.component("monaco-editor", MonacoEditor);
