export default {
  methods: {
    onTemplateNavigate(actionType, data) {
      switch (actionType?.value) {
        case "make-public":
          ProcessMaker.apiClient
            .put(`template/screen/${data.id}/update`, {
              name: data.name,
              description: data.description,
              version: data.version,
              is_public: true,
              is_default_template: false,
            })
            .then(() => {
              ProcessMaker.alert(this.$t("The template has been successfully shared!"), "success");
              this.fetch();
            })
            .catch(error => {
              if (error?.response?.status === 409) {
                error.response?.data?.name.forEach(message => {
                  ProcessMaker.alert(message, 'danger');
                });
              } else {
                ProcessMaker.alert(error.message, "danger");
              }
            });
          break;
        case "delete-template":
          ProcessMaker.confirmModal(
            this.$t("Caution!"),
            this.$t("Are you sure you want to delete the screen template {{item}}?", { item: data.name }),
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
  },
};
