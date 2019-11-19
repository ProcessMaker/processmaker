import Vue from "vue";
import globalProperties from "@processmaker/screen-builder/src/global-properties";
import FileDownload from "./components/file-download.vue";
import {renderer, FormBuilderControls} from "@processmaker/screen-builder";
import formTypes from "./formTypes";

Vue.component("FileDownload", FileDownload);
const {
  FormText
} = renderer;

const TableControl = FormBuilderControls.find(control => control.rendererBinding === "FormMultiColumn");
const RichTextControl = FormBuilderControls.find(control => control.rendererBinding === "FormHtmlEditor");
let FormRecordList = FormBuilderControls.find(control => control.rendererBinding === "FormRecordList");
const FormImage = FormBuilderControls.find(control => control.rendererBinding === "FormImage");

// Remove editable inspector props
FormRecordList.control.inspector = FormRecordList.control.inspector.filter(prop => prop.field !== "editable" && prop.field !== "form");

let controlsDisplay = [
  RichTextControl,
  TableControl,
  FormRecordList,
  FormImage
];

controlsDisplay.push({
  rendererComponent: FormText,
  rendererBinding: "FormText",
  builderComponent: FileDownload,
  builderBinding: "FileDownload",
  control: {
    label: "File Download",
    component: "FileDownload",
    "editor-component": "FormText",
    "editor-config": "FormText",
    config: {
      label: "File Download",
      icon: "fas fa-file-download"
    },
    inspector: [
      {
        type: "FormInput",
        field: "label",
        config: {
          label: "Label",
          helper: "The text to display"
        }
      },
      {
        type: "FormInput",
        field: "name",
        config: {
          label: "Name",
          helper: "The name of the Download"
        }
      }
    ]
  }
});

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
