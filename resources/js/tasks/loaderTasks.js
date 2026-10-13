import * as ScreenBuilder from "@processmaker/screen-builder";
import VueFormElement from "../next/libraries/vueFormElements";
import { setupMain } from "../next/setupMain";
import screenBuilderNext from "../next/screenBuilder";
import "@processmaker/screen-builder/dist/vue-form-builder.css";

setupMain();
screenBuilderNext();
window.ScreenBuilder = ScreenBuilder;
window.Vue.use(ScreenBuilder.default);

window.ProcessMaker.packages = window.temporal?.packages || [];
window.packages = window.ProcessMaker.packages;
