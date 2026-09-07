import { setupMain } from "../../../js/next/setupMain";
import vueFormElements from "../../../js/next/libraries/vueFormElements";
import modelerInspector from "../../../js/next/libraries/modelerInspector";
import modeler from "../../../js/next/modeler";
import screenBuilderNext from "../../../js/next/screenBuilder";
// Load screen-builder for PMBLOCKs
import * as ScreenBuilder from "@processmaker/screen-builder";

window.ScreenBuilder = ScreenBuilder;

setupMain();
screenBuilderNext();
modeler();

window.ProcessMaker.taskDraftsEnabled = window.temporal.taskDraftsEnabled;
window.ProcessMaker.advanced_filter = window.temporal.advanced_filter;
window.ProcessMaker.defaultColumns = window.temporal.defaultColumns;
window.ProcessMaker.isDefaultColumns = window.temporal.isDefaultColumns;
window.ProcessMaker.userConfiguration = window.temporal.userConfiguration;
window.ProcessMaker.showOldTaskScreen = window.temporal.showOldTaskScreen;
window.ProcessMaker.user = window.temporal.user;
window.ProcessMaker.selectedProcess = window.temporal.selectedProcess;
window.ProcessMaker.defaultSavedSearchId = window.temporal.defaultSavedSearchId;
window.ProcessMaker.isTceCustomization = window.temporal.isTceCustomization;
window.ProcessMaker.metricsApiEndpoint = window.temporal.metricsApiEndpoint;
window.ProcessMaker.ellipsisPermission = window.temporal.ellipsisPermission;
window.ProcessMaker.packages = window.temporal.packages;

// Legacy Mix keys (lowercase "m") used by TasksMixin / ParticipantHomeScreen
window.Processmaker = window.Processmaker || {};
window.Processmaker.defaultColumns = window.temporal.defaultColumns;
window.Processmaker.user = window.temporal.user;
window.Processmaker.selectedProcess = window.temporal.selectedProcess;
window.Processmaker.defaultSavedSearchId = window.temporal.defaultSavedSearchId;
