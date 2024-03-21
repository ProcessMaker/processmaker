export default {
  methods: {
    onTemplateNavigate(actionType, data) {
      switch (actionType?.value) {
        case "placeholder-action":
          break;

        case "make-public":
          ProcessMaker.apiClient
            .put(`template/screen/${data.id}/update`, {
              name: data.name,
              description: data.description,
              version: data.version,
              is_public: true,
            })
            .then(() => {
              ProcessMaker.alert(this.$t("The template is now public."), "success");
              this.fetch();
            });
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
  },
};
