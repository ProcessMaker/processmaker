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
        field: "name",
        config: {
          label: "Variable Name",
          name: 'Name',
          helper: "A variable name is a symbolic name to reference information",
          validation: 'regex:/^(?:[A-Z_.a-z])(?:[0-9A-Z_. \/a-z])*$/|required'
        }
      },
      {
        type: "FormInput",
        field: "label",
        config: {
          label: "Label",
          helper: "The label describes the field's name"
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
        type: 'RequiredCheckbox',
        field: 'validation',
        config: {
          label: 'Required',
          helper: 'Prevent form from being submitted unless a file is uploaded'
        }
      }
    ]
  }
};
