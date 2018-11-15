import initialControls from "@processmaker/vue-form-builder/src/form-builder-controls";

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
