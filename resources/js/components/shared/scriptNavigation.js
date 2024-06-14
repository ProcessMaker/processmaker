export default {
    components: {
    },
    data() {
      return {
        assetType: 'script',
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
              case "remove-script":
                let that = this;
                ProcessMaker.confirmModal(
                  this.$t("Caution!"),
                   this.$t("Are you sure you want to delete the script {{item}}? Deleting this asset will break any active tasks that are assigned.", {
                      item: data.title,
                    }),
                    "",
                  function() {
                    ProcessMaker.apiClient
                      .delete("scripts/" + data.id)
                      .then(response => {
                        ProcessMaker.alert(
                          this.$t("The script was deleted."),
                          "success"
                        );
                        that.fetch();
                      });
                  }
                );
                break;
              case 'add-to-project':
                this.showAddToProjectModal(data.title, data.id, data.projects);
                break;
            }
        },
    },
  };
  