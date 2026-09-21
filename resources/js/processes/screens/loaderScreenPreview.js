import * as ScreenBuilder from "@processmaker/screen-builder";
import VueFormElements from "@processmaker/vue-form-elements";
import { setupMain } from "../../next/setupMain";

setupMain();
window.ScreenBuilder = ScreenBuilder;
window.VueFormElements = VueFormElements;
window.Vue.use(ScreenBuilder.default);

window.ProcessMaker.packages = window.temporal?.packages || [];
window.packages = window.ProcessMaker.packages;
