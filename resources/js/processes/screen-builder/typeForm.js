import Vue from "vue";
import globalProperties from "@processmaker/screen-builder/src/global-properties";
import VueFormElements from "@processmaker/vue-form-elements";
import {FormBuilderControls as initialControls} from "@processmaker/screen-builder";
import FileDownloadControl from "./components/file-download-control";
import FileUploadControl from "./components/file-upload-control";

Vue.use(VueFormElements);

initialControls.push(FileUploadControl);
initialControls.push(FileDownloadControl);

// The submit button has by default the 'submit' value
let submitButton = initialControls.find(x => x.control.label === "Submit");
if (submitButton) {
    submitButton.control.config.fieldValue = "submit";
}

ProcessMaker.EventBus.$on("screen-builder-init", (manager) => {
    for (let i = 0; i < initialControls.length; i++) {
        // Load of additional properties for inspector
        Array.prototype.push.apply(initialControls[i].control.inspector, globalProperties[0].inspector);
        manager.addControl(
            initialControls[i].control,
            initialControls[i].rendererComponent,
            initialControls[i].rendererBinding,
            initialControls[i].builderComponent,
            initialControls[i].builderBinding
        );
    }

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
