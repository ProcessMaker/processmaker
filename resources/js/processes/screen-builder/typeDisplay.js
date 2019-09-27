import Vue from "vue";
import {renderer, FormBuilderControls} from "@processmaker/screen-builder";
import FileDownload from "./components/file-download.vue";

Vue.component("FileDownload", FileDownload);
const {
  FormText
} = renderer;

const TableControl = FormBuilderControls.find(control => control.rendererBinding === "FormMultiColumn");
const RichTextControl = FormBuilderControls.find(control => control.rendererBinding === "FormHtmlEditor");
let FormRecordList = FormBuilderControls.find(control => control.rendererBinding === "FormRecordList");

// Remove editable inspector props
FormRecordList.control.inspector = FormRecordList.control.inspector.filter(prop => {
    return prop.field !== 'editable' && prop.field !== 'form';
});

//-- change
const bgcolorProperty = {
  type: "ColorSelect",
  field: "bgcolor",
  config: {
    label: "Element Background color",
    helper: "Set the element's background color",
    options: [{
      value: "alert alert-primary",
      content: "primary"
    },
    {
      value: "alert alert-secondary",
      content: "secondary"
    },
    {
      value: "alert alert-success",
      content: "success"
    },
    {
      value: "alert alert-danger",
      content: "danger"
    },
    {
      value: "alert alert-warning",
      content: "warning"
    },
    {
      value: "alert alert-info",
      content: "info"
    },
    {
      value: "alert alert-light",
      content: "light"
    },
    {
      value: "alert alert-dark",
      content: "dark"
    }
    ]
  }
};

const colorProperty = {
  type: "ColorSelect",
  field: "color",
  config: {
    label: "Text color",
    helper: "Set the element's text color",
    options: [{
      value: "text-primary",
      content: "primary"
    },
    {
      value: "text-secondary",
      content: "secondary"
    },
    {
      value: "text-success",
      content: "success"
    },
    {
      value: "text-danger",
      content: "danger"
    },
    {
      value: "text-warning",
      content: "warning"
    },
    {
      value: "text-info",
      content: "info"
    },
    {
      value: "text-light",
      content: "light"
    },
    {
      value: "text-dark",
      content: "dark"
    }
    ]
  }
};
//

let controlsDisplay = [
  RichTextControl,
  TableControl,
  FormRecordList,
  {
    builderComponent: FormText,
    builderBinding: "FormText",
    rendererComponent: FileDownload,
    rendererBinding: "FileDownload",
    control: {
      label: "File Download",
      component: "FileDownload",
      "editor-component": "FormText",
      config: {
        label: "Download File",
        icon: "fas fa-file-download"
      },
      inspector: [{
        type: "FormInput",
        field: "label",
        config: {
          label: "Text Label",
          helper: "The text to display"
        }
      },
      {
        type: "FormInput",
        field: "name",
        config: {
          label: "Download Name",
          helper: "The name of the Download"
        }
      },
      bgcolorProperty,
      colorProperty
      ]
    }
  }
];

ProcessMaker.EventBus.$on("screen-builder-init", (manager) => {
  for (let item of controlsDisplay) {
    manager.type = "display";
    manager.addControl(
      item.control,
      item.rendererComponent,
      item.rendererBinding,
      item.builderComponent,
      item.builderBinding
    );
  }
});
