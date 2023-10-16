export default {
    components: {
    },
    data() {
      return {
        assetType: null,
        showAddProjectModal: false,
        showTemplateModal: false,
        showCreatePmBlockModal: false,
        dupScript: {
            title: "",
            type: "",
            category: {},
            description: "",
            script_category_id: "",
          },
        errors:[],
      }
    },
    methods: {
        onScriptNavigate(action, data) {
            switch (action.value) {
              case "duplicate-item":
                this.dupScript.title = data.title + " Copy";
                this.dupScript.language = data.language;
                this.dupScript.code = data.code;
                this.dupScript.description = data.description;
                this.dupScript.category = data.category;
                this.dupScript.script_category_id = data.script_category_id;
                this.dupScript.id = data.id;
                this.dupScript.run_as_user_id = data.run_as_user_id;
                this.showModal();
                break;
              case "remove-item":
                ProcessMaker.confirmModal(
                  this.$t("Caution!"),
                  this.$t("Are you sure you want to delete {{item}}? Deleting this asset will break any active tasks that are assigned.", {
                    item: data.title
                  }),
                  "",
                  () => {
                    this.$emit("delete", data);
                  }
                );
                break;
              case 'add-to-project':
                this.showAddToProjectModal(data.title, data.id);
                break;
            }
        },
    },
  };
  