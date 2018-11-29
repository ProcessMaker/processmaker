import initialControls from "@processmaker/vue-form-builder/src/form-builder-controls";

import FileUpload from "./components/form/file-upload"

initialControls[initialControls.length] = {
    rendererComponent: FileUpload,
    rendererBinding: 'FileUpload',
    builderComponent: FileUpload,
    builderBinding: 'FileUpload',
    control: {
        label: 'File Upload',
        component: 'FileUpload',
        'editor-component': 'FileUpload',
        'editor-icon': require('./components/form/file-upload.png'),
        config: {
            label: 'New File Upload'
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
                    label: 'Upload Name',
                    helper: "The name of the upload",
                }
            }
        ]
    }
}

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