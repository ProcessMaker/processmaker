import * as ScreenBuilder from "@processmaker/screen-builder";
import { setupMain } from "../../../js/next/setupMain";
import vueFormElements from "../../../js/next/libraries/vueFormElements";
import modelerInspector from "../../../js/next/libraries/modelerInspector";
import modeler from "../../../js/next/modeler";
import screenBuilderNext from "../../../js/next/screenBuilder";
// Load screen-builder for PMBLOCKs

window.ScreenBuilder = ScreenBuilder;

setupMain();
screenBuilderNext();
modeler();
