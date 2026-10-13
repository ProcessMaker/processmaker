import { setupMain } from "../next/setupMain";
import monaco from "../next/monaco";
import modeler from "../next/modeler";
import * as ScreenBuilder from "@processmaker/screen-builder";
import VueFormElements from "../next/libraries/vueFormElements";
import "../next/libraries/modelerInspector";

import("@processmaker/screen-builder/dist/vue-form-builder.css");
import("@processmaker/vue-form-elements/dist/vue-form-elements.css");

setupMain();
monaco();
modeler();

window.ScreenBuilder = ScreenBuilder;
window.Vue.use(ScreenBuilder.default);

window.ProcessMaker.packages = window.temporal?.packages || [];
window.packages = window.ProcessMaker.packages;

await import("./show");