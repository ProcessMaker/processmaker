import { setupMain } from "../../../js/next/setupMain.js";
import screenBuilderNext from "../../../js/next/screenBuilder.js";
import modeler from "../../../js/next/modeler.js";
import "../../../js/next/libraries/vueFormElements.js";
import "../../../js/next/libraries/modelerInspector.js";
import * as ScreenBuilder from "@processmaker/screen-builder";

window.ScreenBuilder = ScreenBuilder;

setupMain();
screenBuilderNext();
modeler();

window.ProcessMaker.caseNumber = request.case_number;
window.ProcessMaker.modeler = {
  xml: temporal.bpmn,
  configurables: [],
  requestCompletedNodes: inflightData.requestCompletedNodes,
  requestInProgressNodes: inflightData.requestInProgressNodes,
  requestIdleNodes: inflightData.requestIdleNodes,
  requestId: inflightData.requestId,
};

window.ProcessMaker.EventBus.$on("modeler-start", ({ loadXML }) => {
  loadXML(window.ProcessMaker.modeler.xml);
});

window.PM4ConfigOverrides = {
  requestFiles: window.temporal.requestFiles,
};

window.ProcessMaker.PMBlockList = window.temporal.pmBlockList;
