import { renderer, FormBuilderControls, globalProperties } from "@processmaker/screen-builder";
import formTypes from "./formTypes";

const {
  FormText,
} = renderer;

const TableControl = FormBuilderControls.find((control) => control.rendererBinding === "FormMultiColumn");
const RichTextControl = FormBuilderControls.find((control) => control.rendererBinding === "FormHtmlEditor");
const FormRecordList = FormBuilderControls.find((control) => control.rendererBinding === "FormRecordList");
const FormImage = FormBuilderControls.find((control) => control.rendererBinding === "FormImage");
const FormAvatar = FormBuilderControls.find((control) => control.rendererBinding === "FormAvatar");
const FormLoop = FormBuilderControls.find((control) => control.rendererBinding === "FormLoop");
const FormNestedScreen = FormBuilderControls.find((control) => control.rendererBinding === "FormNestedScreen");
const FileDownloadControl = FormBuilderControls.find((control) => control.builderBinding === "FileDownload");
const FormListTable = FormBuilderControls.find((control) => control.rendererBinding === "FormListTable");
const FormAnalyticsChart = FormBuilderControls.find((control) => control.rendererBinding === "FormAnalyticsChart");
const FormCollectionRecordControl = FormBuilderControls.find((control) => control.rendererBinding === "FormCollectionRecordControl");
// Remove editable inspector props
FormRecordList.control.inspector = FormRecordList.control.inspector.filter((prop) => prop.field !== "editable" && prop.field !== "form");

// Modify record list description when used in a display screen
FormRecordList.control.popoverContent = "Format content in a table structure";

const controlsDisplay = [
  RichTextControl,
  TableControl,
  FormRecordList,
  FormImage,
  FormAvatar,
  FormLoop,
  FormNestedScreen,
  FileDownloadControl,
  FormListTable,
  FormAnalyticsChart,
  FormCollectionRecordControl,
];

ProcessMaker.EventBus.$on("screen-builder-init", (manager) => {
  controlsDisplay.forEach((item) => {
    item.control.inspector.push(...globalProperties[0].inspector);
    manager.type = formTypes.display;
    manager.addControl(
      item.control,
      item.rendererComponent,
      item.rendererBinding,
      item.builderComponent,
      item.builderBinding,
    );
  });
});
