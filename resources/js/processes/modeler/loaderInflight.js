import { setupMain } from "../../next/setupMain";
import monaco from "../../next/monaco";
import modeler from "../../next/modeler";
import "../../next/libraries/vueFormElements";
import "../../next/libraries/modelerInspector";
import * as ScreenBuilder from "@processmaker/screen-builder";
import * as ModelerLib from "@processmaker/modeler";
import VueFormElements from "@processmaker/vue-form-elements";

import("@processmaker/screen-builder/dist/vue-form-builder.css");
import("@processmaker/vue-form-elements/dist/vue-form-elements.css");

setupMain();
modeler();
monaco();

window.ScreenBuilder = ScreenBuilder;
// CJS default is the Mix `require()` object. The ESM namespace is not extensible
// and Vue throws "Cannot add property _Ctor" if it is registered as a component.
window.Modeler = ModelerLib.default ?? { ...ModelerLib };

window.Vue.use(VueFormElements);
window.Vue.use(ScreenBuilder.default);

window.ProcessMaker.packages = window.temporal?.packages || [];
window.packages = window.ProcessMaker.packages;

const boot = window.temporal || {};

window.ProcessMaker.defaultEmailNotification = boot.defaultEmailNotification;
window.ProcessMaker.multiplayer = boot.multiplayer;
window.ProcessMaker.PMBlockList = boot.PMBlockList;
window.ProcessMaker.ExternalIntegrationsList = boot.ExternalIntegrationsList;
window.ProcessMaker.modeler = boot.modeler;
window.ProcessMaker.tceCustomizationEnable = boot.tceCustomizationEnable;

window.ProcessMaker.EventBus.$on("modeler-start", ({ loadXML, addWarnings, addBreadcrumbs }) => {
  loadXML(window.ProcessMaker.modeler.xml);
  addWarnings(boot.warnings || []);
  addBreadcrumbs(boot.breadcrumbData || []);
});
