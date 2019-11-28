import FileUpload from "./form/file-upload";

export default {
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
        field: "accept",
        config: {
          label: "File Accepted",
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
};
