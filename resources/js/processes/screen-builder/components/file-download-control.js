import FileDownload from "./file-download";
import { renderer } from "@processmaker/screen-builder";
const { FormText } = renderer;

export default {
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
        inspector: [{
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
}