import * as ScreenBuilder from "@processmaker/screen-builder";
import { setupMain } from "../../js/next/setupMain.js";
import vueFormElements from "../../js/next/libraries/vueFormElements";
import "@processmaker/screen-builder/dist/vue-form-builder.css";

window.ScreenBuilder = ScreenBuilder;
setupMain();
window.Vue.use(ScreenBuilder.default);

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
