export default {
  components: {
  },
  data() {
    return {
      assetType: 'process',
      showAddProjectModal: false,
      showTemplateModal: false,
      showCreatePmBlockModal: false,
      showModalSaveVersion: false,
    }
  },
  methods: {
    onProcessNavigate(action, data) {
        let putData = {
          name: data.name,
          description: data.description,
        };
        switch (action.value) {
          case "unpause-start-timer":
            putData.pause_timer_start = false;
            ProcessMaker.apiClient
                .put("processes/" + data.id, putData)
                .then(response => {
                  ProcessMaker.alert(
                      this.$t("The process was unpaused."),
                      "success"
                  );
                  this.$emit("reload");
                });
            break;
          case "pause-start-timer":
            putData.pause_timer_start = true;
            ProcessMaker.apiClient
                .put("processes/" + data.id, putData)
                .then(response => {
                  ProcessMaker.alert(
                      this.$t("The process was paused."),
                      "success"
                  );
                  this.$emit("reload");
                });
            break;
          case "create-template":
            this.showCreateTemplateModal(data.name, data.id);
            break;
          case "create-pm-block":
            this.showPmBlockModal(data.name, data.id);
            break;
          case "restore-item":
            ProcessMaker.apiClient
                .put("processes/" + data.id + "/restore")
                .then(response => {
                  ProcessMaker.alert(
                      this.$t("The process was restored."),
                      "success"
                  );
                  this.$emit("reload");
                });
            break;
          case "archive-item":
            ProcessMaker.confirmModal(
                this.$t("Caution!"),
                this.$t("Are you sure you want to archive the process") +
                data.name +
                "?",
                "",
                () => {
                  ProcessMaker.apiClient
                      .delete("processes/" + data.id)
                      .then(response => {
                        ProcessMaker.alert(
                            this.$t("The process was archived."),
                            "success"
                        );
                        this.$refs.pagination.loadPage(1);
                      });
                }
            );
            break;
          case "archive-item-launchpad":
            ProcessMaker.confirmModal(
              this.$t("Caution!"),
              this.$t("Are you sure you want to archive the process") +
              data.name +
              "?",
              "",
              () => {
                ProcessMaker.apiClient
                  .delete("processes/" + data.id)
                  .then(response => {
                    ProcessMaker.alert(
                      this.$t("The process was archived."),
                      "success"
                    );
                    this.goBack();
                  });
              }
            );
            break;

          case "download-bpmn":
            ProcessMaker.confirmModal(
                this.$t("Caution!"),
                this.$t("Are you sure you want to download the BPMN definition of the process?"),
                "",
                () => {
                  ProcessMaker.apiClient
                      .get("processes/" + data.id + "/bpmn")
                      .then(response => {

                        const link = document.createElement("a");
                        const file = new Blob([response.data], { type: 'text/plain' });

                        link.href = URL.createObjectURL(file);
                        link.download = "bpmnProcess.xml";
                        link.click();
                        URL.revokeObjectURL(link.href);

                        ProcessMaker.alert(
                            this.$t("The process BPMN has been downloaded."),
                            "success"
                        );
                      });
                }
            );
            break;
            case 'add-to-project':
              this.showAddToProjectModal(data.name, data.id, data.projects);
            break;
            case 'edit-launchpad':
              this.showAddToModalSaveVersion(data.name, data.id);
            break;
        }
      },
  },
};
