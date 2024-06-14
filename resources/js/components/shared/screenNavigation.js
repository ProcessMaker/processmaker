export default {
    components: {
    },
    data() {
      return {
        assetType: 'screen',
        showAddProjectModal: false,
        showTemplateModal: false,
        showCreatePmBlockModal: false,
        dupScreen: {
            title: "",
            type: "",
            category: {},
            screen_category_id: "",
            description: ""
        },
        errors:[],
      }
    },
    methods: {
        onScreenNavigate(actionType, data, index) {
            if (actionType.value) {
              switch (actionType.value) {
              case "duplicate-item":
                this.dupScreen.title = data.title + ' ' + this.$t('Copy');
                this.dupScreen.type = data.type;
                this.dupScreen.category = data.category;
                this.dupScreen.screen_category_id = data.screen_category_id;
                this.dupScreen.description = data.description;
                this.dupScreen.id = data.id;
                this.showModal();
                break;
              case "remove-screen":
                let that = this;
                ProcessMaker.confirmModal(
                  this.$t("Caution!"),
                   this.$t("Are you sure you want to delete the screen {{item}}? Deleting this asset will break any active tasks that are assigned.", {
                      item: data.title,
                    }),
                    "",
                  function() {
                    ProcessMaker.apiClient
                      .delete("screens/" + data.id)
                      .then(response => {
                        ProcessMaker.alert(
                          this.$t("The screen was deleted."),
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
              case 'create-template':
                this.showCreateTemplateModal(data.title, data.id, data.type);
                break;
            }
          } else {
              switch (actionType) {
              case "edit-screen":
                let link = "/designer/screen-builder/" + data.id + "/edit";
                return link;
              }
          }
        },
    },
  };
  