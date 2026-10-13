import Mustache from "mustache";
import "../next/libraries/vueFormElements";
import screenBuilder from "../next/screenBuilder";
import * as ScreenBuilder from "@processmaker/screen-builder";
import { setupMain } from "../next/setupMain";
import "./preview";
import ScreenDetail from "./components/screenDetail.vue";

window.Mustache = Mustache;
setupMain();
screenBuilder();

Vue.use(ScreenBuilder.default);
Vue.component("ScreenDetail", ScreenDetail);
