export default {
  methods: {
    onTemplateNavigate(actionType, data) {
      console.log(actionType);
      console.log(data);
      switch (actionType?.value) {
        case "edit-template":
          this.goToScreenBuilder(data.id);
          break;
        case "placeholder-action-2":
          break;
        case "delete-template":
          ProcessMaker.confirmModal(
            this.$t("Caution!"),
            this.$t("Are you sure you want to delete the screen template {{item}}?", { item: data.title }),
            "",
            () => {
              ProcessMaker.apiClient.delete(`template/screen/${data.id}`).then(() => {
                ProcessMaker.alert(this.$t("The template was deleted."), "success");
                this.fetch();
              });
            },
          );
          break;

        default:
          break;
      }
    },
    goToScreenBuilder(data) {
      ProcessMaker.apiClient.get(`/screen-builder/screen/${data}`)
      .then((response) => {
        window.location = `/designer/screen-builder/${response.data.id}/edit`
      }).catch(error => {
        ProcessMaker.alert(error.response?.data?.message, "danger");
      });
    }
  },
};
