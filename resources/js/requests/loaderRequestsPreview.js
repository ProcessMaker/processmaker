import Mustache from "mustache";
import "../next/libraries/vueFormElements";
import screenBuilder from "../next/screenBuilder";
import * as ScreenBuilder from "@processmaker/screen-builder";
import initializeScreenCacheFromMeta from "../next/initializeScreenCacheFromMeta";
import { setupMain } from "../next/setupMain";
import "./preview";
import ScreenDetail from "./components/screenDetail.vue";

window.Mustache = Mustache;
setupMain();
initializeScreenCacheFromMeta();
screenBuilder();

Vue.use(ScreenBuilder.default);
Vue.component("ScreenDetail", ScreenDetail);
