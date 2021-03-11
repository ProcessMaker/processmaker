import Vue from "vue";
import globalProperties from "@processmaker/screen-builder/src/global-properties";
import {renderer, FormBuilderControls} from "@processmaker/screen-builder";
import formTypes from "./formTypes";

const {
  FormText
} = renderer;

const TableControl = FormBuilderControls.find(control => control.rendererBinding === "FormMultiColumn");
const RichTextControl = FormBuilderControls.find(control => control.rendererBinding === "FormHtmlEditor");
let FormRecordList = FormBuilderControls.find(control => control.rendererBinding === "FormRecordList");
const FormImage = FormBuilderControls.find(control => control.rendererBinding === "FormImage");
const FormLoop = FormBuilderControls.find(control => control.rendererBinding === "FormLoop");
const FormNestedScreen = FormBuilderControls.find(control => control.rendererBinding === "FormNestedScreen");
const FileDownloadControl = FormBuilderControls.find(control => control.builderBinding === "FileDownload");

// Remove editable inspector props
FormRecordList.control.inspector = FormRecordList.control.inspector.filter(prop => prop.field !== "editable" && prop.field !== "form");

let controlsDisplay = [
  RichTextControl,
  TableControl,
  FormRecordList,
  FormImage,
  FormLoop,
  FormNestedScreen,
  FileDownloadControl,
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
      item.builderBinding
    );
  });
});
