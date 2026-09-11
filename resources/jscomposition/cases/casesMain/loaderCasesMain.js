import { setupMain } from "../../../js/next/setupMain.js";
import screenBuilderNext from "../../../js/next/screenBuilder.js";
import * as ScreenBuilder from "@processmaker/screen-builder";
import vueFormElements from "../../../js/next/libraries/vueFormElements";

window.ScreenBuilder = ScreenBuilder;

setupMain();
screenBuilderNext();

window.ProcessMaker.packages = window.temporal.packages;
window.ProcessMaker.user = window.temporal.user;
