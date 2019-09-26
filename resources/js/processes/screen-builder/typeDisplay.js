import {renderer, FormBuilderControls} from "@processmaker/screen-builder";
import FileDownload from "./components/file-download.vue";
import {FormHtmlEditor} from "@processmaker/vue-form-elements";
import formTypes from "./formTypes";

Vue.component("FileDownload", FileDownload);

const {
    FormMultiColumn,
    FormText,
    FormRecordList
} = renderer;
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

const TableControl = FormBuilderControls.find(control => control.editorComponent === FormMultiColumn);
const RichTextControl = FormBuilderControls.find(control => control.editorComponent === FormHtmlEditor);

let initialControls = [
RichTextControl,
{
    builderComponent: FormText,
    builderBinding: "FormText",
    rendererComponent: FileDownload,
    rendererBinding: "FileDownload",
    control: {
        label: "File Download",
        component: "FileDownload",
        "editor-component": "FormText",
        "editor-control": "FormText",
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
},
TableControl,
{
    editorComponent: FormText,
    editorBinding: "FormText",
    rendererComponent: FormRecordList,
    rendererBinding: "FormRecordList",
    control: {
        label: "Record List",
        component: "FormRecordList",
        "editor-component": "FormText",
        "editor-control": "FormText",
        config: {
            name: "",
            label: "New Record List",
            editable: false,
            fields: [],
            icon: "fas fa-th-list",
            form: ""
        },
        inspector: [{
            type: "FormInput",
            field: "name",
            config: {
                label: "List Name",
                name: "List Name",
                validation: "required",
                helper: "The data name for this list"
            }
        },
        {
            type: "FormInput",
            field: "label",
            config: {
                label: "List Label",
                helper: "The label describes this record list"
            }
        },
        {
            type: "OptionsList",
            field: "fields",
            config: {
                label: "Fields List",
                helper: "List of fields to display in the record list"
            }
        },
        {
            type: "PageSelect",
            field: "form",
            config: {
                label: "Record Form",
                helper: "The form to use for adding/editing records"
            }
        },
        bgcolorProperty,
        colorProperty

        ]

    }

}
];

ProcessMaker.EventBus.$on("screen-builder-init", (manager) => {
    for (let i = 0; i < initialControls.length; i++) {
        manager.type = formTypes.display;

        manager.addControl(
            initialControls[i].control,
            initialControls[i].rendererComponent,
            initialControls[i].rendererBinding,
            initialControls[i].builderComponent,
            initialControls[i].builderBinding
        );
    }
});
