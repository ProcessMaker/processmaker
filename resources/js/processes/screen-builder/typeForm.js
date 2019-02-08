import Vue from 'vue';
import initialControls from "@processmaker/vue-form-builder/src/form-builder-controls";
import globalProperties from "@processmaker/vue-form-builder/src/global-properties";
import FormText from "@processmaker/vue-form-builder/src/components/renderer/form-text";
import FileDownload from "./components/file-download"
import FileUpload from "./components/form/file-upload"

Vue.component('FileUpload', FileUpload);
Vue.component('FileDownload', FileDownload);2

initialControls.push({
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
})
initialControls.push({
    rendererComponent: FormText,
    rendererBinding: 'FormText',
    builderComponent: FileDownload,
    builderBinding: 'FileDownload',
    control: {
        label: 'File Download',
        component: 'FileDownload',
        'editor-component': 'FormText',
        'editor-icon': require('./components/file-download.png'),
        config: {
            label: 'New File Download'
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
});

ProcessMaker.EventBus.$on('screen-builder-init', (manager) => {

    for (let i = 0; i < initialControls.length; i++) {
        //Load of additional properties for inspector
        Array.prototype.push.apply(initialControls[i].control.inspector, globalProperties[0].inspector);
        manager.addControl(
            initialControls[i].control,
            initialControls[i].rendererComponent,
            initialControls[i].rendererBinding,
            initialControls[i].builderComponent,
            initialControls[i].builderBinding
        );
    }
}, );