import Vue from "vue";
import VueFormElements from "@processmaker/vue-form-elements";
import { FormBuilderControls as initialControls, globalProperties } from "@processmaker/screen-builder";
import { Multiselect } from "@processmaker/vue-multiselect";

Vue.use(VueFormElements);
Vue.component("Multiselect", Multiselect);

// The submit button has by default the 'submit' value
const submitButton = initialControls.find((x) => x.control.label === "Submit");
if (submitButton) {
  submitButton.control.config.fieldValue = "submit";
}

ProcessMaker.EventBus.$on("screen-builder-init", (manager) => {
  initialControls.forEach((config) => {
    config.control.inspector.push(...globalProperties[0].inspector);

    if (
      config.control.component !== "FormListTable"
      && config.control.component !== "FormAnalyticsChart"
      && config.control.component !== "FormAvatar"
      && config.control.component !== "LinkButton"
    ) {
      manager.addControl(
        config.control,
        config.rendererComponent,
        config.rendererBinding,
        config.builderComponent,
        config.builderBinding,
      );
    }
  });
});
