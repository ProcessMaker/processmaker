import {renderer, FormBuilderControls} from "@processmaker/screen-builder";
import FileDownload from "./components/file-download.vue";

const {
    FormMultiColumn,
    FormText,
    FormRecordList
} = renderer;

const TableControl = FormBuilderControls.find(control => control.editorComponent === FormMultiColumn);

let initialControls = [{
    builderComponent: FormText,
    builderBinding: "FormText",
    rendererComponent: FormText,
    rendererBinding: "FormText",
    control: {
        label: "Text",
        component: "FormText",
        "editor-component": "FormText",
        config: {
            label: "New Text",
            fontSize: "1em",
            fontWeight: "normal",
            icon: "fas fa-align-justify"
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
            type: "FormSelect",
            field: "fontWeight",
            config: {
                label: "Font Weight",
                helper: "The weight of the text",
                options: [{
                    value: "normal",
                    content: "Normal"
                },
                {
                    value: "bold",
                    content: "Bold"
                }
                ]
            }
        },

        {
            type: "FormSelect",
            field: "fontSize",
            config: {
                label: "Font Size",
                helper: "The size of the text in em",
                options: [{
                    value: "0.5em",
                    content: "0.5"
                },
                {
                    value: "1em",
                    content: "1"
                },
                {
                    value: "1.5em",
                    content: "1.5"
                },
                {
                    value: "2em",
                    content: "2"
                }
                ]
            }
        }

        ]
    }
},
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
        }
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
        }

        ]

    }

}

];

ProcessMaker.EventBus.$on("screen-builder-init", (manager) => {
    for (let i = 0; i < initialControls.length; i++) {
        manager.addControl(
            initialControls[i].control,
            initialControls[i].rendererComponent,
            initialControls[i].rendererBinding,
            initialControls[i].builderComponent,
            initialControls[i].builderBinding
        );
    }
});
