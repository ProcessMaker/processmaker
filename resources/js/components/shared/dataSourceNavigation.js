export default {
  components: {
  },
  data() {
    return {}
  },
  methods: {
    /**
     * navigation for data sources actions
     */
    onDataSourceNavigate(action, data) {
      switch (action.value) {
        case "remove-item":
          this.doDataSourceDelete(data);
          break;
        case 'add-to-project':
          this.showAddToProjectModal(data.name, data.id, data.projects);
          break;
        case "edit-item":
          this.editDataSourse(data);
          break;  
      }
    },
    /**
     * delete data source
     */
    doDataSourceDelete(item) {
      ProcessMaker.confirmModal(this.$t("Caution!"), this.$t("Are you sure you want to delete {{item}}?", { item: item.name }), "", () => {
        ProcessMaker.apiClient
          .delete(`data_sources/${item.id}`)
          .then(() => {
            ProcessMaker.alert(this.$t("The Data Connector was deleted."), "success");
            this.fetch();
          })
          .catch((e) => {
            ProcessMaker.alert(e.response.data.errors.delete[0], "danger");
          });
      });
    },
    /**
     * go to edit data sources
     */
    editDataSourse(row) {
      window.location.href = `/designer/data-sources/${row.id}/edit`;
    },
  },
};
