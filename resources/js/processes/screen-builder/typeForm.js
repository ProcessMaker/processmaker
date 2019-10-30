import Vue from "vue";
import globalProperties from "@processmaker/screen-builder/src/global-properties";
import FileDownload from "./components/file-download";
import FileUpload from "./components/form/file-upload";
import VueFormElements from "@processmaker/vue-form-elements";
import {renderer, FormBuilderControls as initialControls} from "@processmaker/screen-builder";

Vue.use(VueFormElements);

const {FormText} = renderer;

Vue.component("FileUpload", FileUpload);
Vue.component("FileDownload", FileDownload);

initialControls.push({
  rendererComponent: FileUpload,
  rendererBinding: "FileUpload",
  builderComponent: FileUpload,
  builderBinding: "FileUpload",
  control: {
    label: "File Upload",
    component: "FileUpload",
    "editor-component": "FileUpload",
    "editor-control": "FileUpload",
    config: {
      label: "New File Upload",
      icon: "fas fa-file-upload"
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
        field: "type",
        config: {
          label: "Field Accepted",
          helper: "Common file types: application/msword, image/gif, image/jpeg, application/pdf, application/vnd.ms-powerpoint, application/vnd.ms-excel, text/plain"
        }
      },
      {
        type: "FormInput",
        field: "name",
        config: {
          label: "Name",
          helper: "The name of the upload"
        }
      }
    ]
  }
});

initialControls.push({
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
      label: "New File Download",
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

// The submit button has by default the 'submit' value
let submitButton = initialControls.find(x => x.control.label === "Submit");
if (submitButton) {
  submitButton.control.config.fieldValue = "submit";
}

ProcessMaker.EventBus.$on("screen-builder-init", (manager) => {
  initialControls.forEach((item) => {
    // Load of additional properties for inspector
    item.control.inspector.push(...globalProperties[0].inspector);
    manager.addControl(
      item.control,
      item.rendererComponent,
      item.rendererBinding,
      item.builderComponent,
      item.builderBinding
    );
  });

  // Validations for field names
  for (let i = 0; i < initialControls.length; i++) {
    let item = initialControls[i];

    if (item.control === undefined || item.control.inspector === undefined) {
      continue;
    }

    for (let j = 0; j < item.control.inspector.length; j++) {
      let config = item.control.inspector[j].config;
      if (config.label === "Key Name") {
        config.validation = "regex:/^(?:[A-Z_a-z])(?:[0-9A-Z_a-z])*$/|required";
      }
    }
  }
});
