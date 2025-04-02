import { Multiselect } from "@processmaker/vue-multiselect";

Vue.component("Multiselect", Multiselect);

ProcessMaker.EventBus.$on("screen-builder-init", (manager) => {
  const { FormBuilderControls, globalProperties } = window.ScreenBuilder;
  const initialControls = FormBuilderControls;
  // The submit button has by default the 'submit' value
  const submitButton = initialControls.find((x) => x.control.label === "Submit");
  if (submitButton) {
    submitButton.control.config.fieldValue = "submit";
  }

  initialControls.forEach((config) => {
    config.control.inspector.push(...globalProperties[0].inspector);

    if (
      config.control.component !== "FormListTable"
      && config.control.component !== "FormAnalyticsChart"
      && config.control.component !== "FormAvatar"
      && config.control.component !== "LinkButton"
      && config.control.component !== "FormCollectionViewControl"
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
