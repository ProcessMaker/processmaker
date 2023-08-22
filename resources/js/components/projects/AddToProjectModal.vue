<template>
    <div>
      <modal
        id="addToProject"
        :title="title" 
        @addToProject="addToProject"
        @close="close"
        :setCustomButtons="true"
        :customButtons="customModalButtons"
        size="md"
      >
        <template>
            <required></required>
            <project-select
                v-model="projects"
                :label="$t('Select Project')"
                api-get="projects"
                api-list="projects"
                name="proeject"
                :errors="addError.project"
            />
        </template>
      </modal>
    </div>
  </template>
  
  <script>
  import { FormErrorsMixin, Modal, Required} from "SharedComponents";
  import ProjectSelect from "./ProjectSelect.vue";
  
  export default {
    components: { Modal, ProjectSelect, Required },
    mixins: [FormErrorsMixin],
    props: ["assetName", "currentUserId"],
    data() {
      return {
        errors: {},
        projects: [],
        addError: {},
        showModal: false,
        disabled: true,
        customModalButtons: [
          {"content": "Cancel", "action": "close", "variant": "outline-secondary", "disabled": false, "hidden": false},
          {"content": "Assign", "action": "addToProject", "variant": "primary", "disabled": true, "hidden": false},
        ],
      }
    },
      computed: {
        title() {
          return this.$t('Assign to Project');
        },
      },
      watch: {
        projects() {
            this.customModalButtons[1].disabled = this.projects.length > 0 ? false : true;
        }
      },  
      methods: {
        show() {
          this.customModalButtons[1].disabled = this.projects.length > 0 ? false : true;
          this.$bvModal.show('addToProject');
        },
        close() {
          this.$bvModal.hide('addToProject');
          this.clear();
          this.errors = {};
        },
        clear() {
          this.projects = [];
        },
        addToProject() {
          let formData = new FormData();
          formData.append("asset_type", this.assetType);
          formData.append("asset_id", this.asset_id);
          formData.append("projects", this.projects);
          this.customModalButtons[1].disabled = true;
          ProcessMaker.apiClient.post("/projects/assets/assign", formData)
          .then(response => {
            console.log("RESPONSE", response);
            ProcessMaker.alert(this.$t("Asset successfully assigned to project"), "success");
            this.close();
          }).catch(error => {
            this.errors = error.response.data;
            this.customModalButtons[1].disabled = false;
            if (this.errors.hasOwnProperty('errors')) {
              this.errors = this.errors.errors;
            } else {
              const message = error.response.data.error;
              ProcessMaker.alert(this.$t(message), "danger");
            }
          });
        },
      },
    };
</script>
  
<style scoped>

    .overflow-modal {
    max-height: 30vh;
    overflow-y: auto;
    }
</style>
  