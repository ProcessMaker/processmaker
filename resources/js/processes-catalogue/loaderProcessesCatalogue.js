import { setupMain } from "../../js/next/setupMain.js";
import screenBuilderNext from "../../js/next/screenBuilder.js";
import * as ScreenBuilder from "@processmaker/screen-builder";
import vueFormElements from "../../js/next/libraries/vueFormElements";

window.ScreenBuilder = ScreenBuilder;

setupMain();
screenBuilderNext();

window.ProcessMaker.isDocumenterInstalled = window.temporal.isDocumenterInstalled;
window.ProcessMaker.permission = window.temporal.permission;
window.ProcessMaker.defaultSavedSearch = window.temporal.defaultSavedSearch;
window.ProcessMaker.isTceCustomization = window.temporal.isTceCustomization;
window.ProcessMaker.metricsApiEndpoint = window.temporal.metricsApiEndpoint;
window.ProcessMaker.userConfiguration = window.temporal.userConfiguration;
window.ProcessMaker.user = window.temporal.user;
window.ProcessMaker.packages = window.temporal.packages;

// Legacy Mix keys (lowercase "m") used by shared catalogue components
window.Processmaker = window.Processmaker || {};
window.Processmaker.user = window.temporal.user;
window.Processmaker.userConfiguration = window.temporal.userConfiguration;
window.Processmaker.defaultSavedSearch = window.temporal.defaultSavedSearch;
