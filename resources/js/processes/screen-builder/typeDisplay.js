import FormText from "@processmaker/vue-form-builder/src/components/renderer/form-text";
import FormMultiColumn from "@processmaker/vue-form-builder/src/components/renderer/form-multi-column"
import FormRecordList from "@processmaker/vue-form-builder/src/components/renderer/form-record-list"
import FileDownload from "./components/file-download"

import {
    FormInput,
    FormSelect,
    FormTextArea,
    FormCheckbox,
    FormRadioButtonGroup,
    FormDatePicker
} from "@processmaker/vue-form-elements/src/components";

let initialControls = [{
        builderComponent: FormText,
        builderBinding: 'FormText',
        rendererComponent: FormText,
        rendererBinding: 'FormText',
        control: {
            label: 'Text',
            component: 'FormText',
            'editor-component': 'FormText',
            'editor-icon': require('./font-solid.svg'),
            config: {
                label: 'New Text',
                fontSize: '1em',
                fontWeight: 'normal'
            },
            inspector: [{
                    type: "FormInput",
                    field: "label",
                    config: {
                        label: "Text Label",
                        helper: "The text to display",
                    }
                },
                {
                    type: "FormSelect",
                    field: "fontWeight",
                    config: {
                        label: "Font Weight",
                        helper: "The weight of the text",
                        options: [{
                                value: 'normal',
                                content: 'Normal'
                            },
                            {
                                value: 'bold',
                                content: 'Bold'
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
                                value: '0.5em',
                                content: '0.5'
                            },
                            {
                                value: '1em',
                                content: '1'
                            },
                            {
                                value: '1.5em',
                                content: '1.5'
                            },
                            {
                                value: '2em',
                                content: '2'
                            },
                        ]
                    }
                },


            ]
        }
    },
    {
        builderComponent: FormText,
        builderBinding: 'FormText',
        rendererComponent: FileDownload,
        rendererBinding: 'FileDownload',
        control: {
            label: 'File Download',
            component: 'FileDownload',
            'editor-component': 'FormText',
            'editor-icon': require('./components/file-download.png'),
            config: {
                label: 'Download File',
            },
            inspector: [{
                type: "FormInput",
                field: "label",
                config: {
                    label: "Text Label",
                    helper: "The text to display",
                }
            },
            {
                type: "FormInput",
                field: "name",
                config: {
                    label: 'Download Name',
                    helper: "The name of the Download",
                }
            }
          ]
        }
    },
    {
        editorComponent: FormMultiColumn,
        editorBinding: 'FormMultiColumn',
        rendererComponent: FormMultiColumn,
        rendererBinding: 'FormMultiColumn',
        control: {
            label: "Multi Column",
            component: 'FormMultiColumn',
            "editor-component": "MultiColumn",
            'editor-icon': require('./columns-solid.svg'),
            container: true,
            // Default items container
            items: [
                [],
                []
            ],
            config: {},
            inspector: [{
                    type: "FormText",
                    config: {
                        label: "MultiColumn",
                    }
                }

            ]
        },
    },
    {
        editorComponent: FormText,
        editorBinding: 'FormText',
        rendererComponent: FormRecordList,
        rendererBinding: 'FormRecordList',
        control: {
            label: "Record List",
            component: 'FormRecordList',
            "editor-component": "FormText",
            'editor-icon': require('./th-list-solid.svg'),
            config: {
                name: '',
                label: "New Record List",
                editable: false,
                fields: [],
                form: ''
            },
            inspector: [{
                    type: "FormInput",
                    field: "name",
                    config: {
                        label: "List Name",
                        name: 'List Name',
                        validation: 'required',
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
                        label: 'Fields List',
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

        },


    }

]


ProcessMaker.EventBus.$on('screen-builder-init', (manager) => {
    for (var i = 0; i < initialControls.length; i++) {
        manager.addControl(
            initialControls[i].control,
            initialControls[i].rendererComponent,
            initialControls[i].rendererBinding,
            initialControls[i].builderComponent,
            initialControls[i].builderBinding
        );
    }
});