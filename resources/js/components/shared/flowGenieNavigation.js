export default {
  components: {
  },
  data() {
    return {};
  },
  methods: {
    /**
     * navigation for flow genies actions
     */
    onFlowGenieNavigate(action, data) {
      const name = data.name || data.title;
      switch (action.value) {
        case "edit-item":
          this.editFlowGenie(data);
          break;
        case "copy-item":
          this.doDuplicateFlowGenie(data);
          break;
        case "add-to-project":
          this.showAddToProjectModal(name, data.id);
          break;
        case "remove-item":
          this.doDeleteFlowGenie(data);
          break;
        default:
          break;
      }
    },
    /**
     * Open the shared Create Flow Genie modal in duplicate mode.
     */
    doDuplicateFlowGenie(data) {
      this.showFlowGenieCopyModal(data);
    },
    /**
     * go to edit flow genie
     */
    editFlowGenie(row) {
      window.location.href = `/designer/flow-genies/${row.id}/edit`;
    },
    /**
     * do delete decision table
     */
    doDeleteFlowGenie(item) {
      ProcessMaker.confirmModal(this.$t("Caution!"), this.$t("Are you sure you want to delete {{item}}?", { item: item.title }), "", () => {
        ProcessMaker.apiClient
          .delete(`package-ai/flow_genies/${item.id}`)
          .then(() => {
            ProcessMaker.alert(this.$t("The Flow Genie was deleted."), "success");
            if (this.data.data.length === 1) {
              this.page -= 1;
            }
            this.fetch();
          });
      });
    },
  },
};
