export default {
  components: {
  },
  data() {
    return {};
  },
  methods: {
    onDecisionTableNavigate(action, data) {
      switch (action.value) {
        case "edit-item":
          this.editDecisionTable(data);
          break;  
        case "configure-item":
          this.configDecisionTable(data);
          break;
        case 'add-to-project':
          this.showAddToProjectModal(data.title, data.id);
          break;
        case "export-item":
          this.doExportDecisionTable(data);
          break;
        case "remove-item":
          this.doDeleteDecisionTable(data);
          break;
      }
    },
    editDecisionTable(row) {
      window.location.href = `/designer/decision-tables/table-builder/${row.id}/edit`;
    },
    configDecisionTable(row) {
      window.location.href = `/designer/decision-tables/${row.id}/edit`;
    },
    doDeleteDecisionTable(item) {
      ProcessMaker.confirmModal(this.$t("Caution!"), this.$t("Are you sure you want to delete {{item}}?", { item: item.title }), "", () => {
        ProcessMaker.apiClient
          .delete(`decision_tables/${item.id}`)
          .then(() => {
            ProcessMaker.alert(this.$t("The Decision Table was deleted."), "success");
            if (this.data.data.length === 1) {
              this.page -= 1;
            }
            this.fetch();
          });
      });
    },
    doExportDecisionTable(row) {
      window.location.href = `/designer/decision-tables/${row.id}/export`;
    },
  },
};
