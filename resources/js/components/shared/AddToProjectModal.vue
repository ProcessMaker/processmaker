<template>
  <div>
    <modal id="addToProject" :title="title" @addToProject="addToProject" @close="close" :setCustomButtons="true"
      :customButtons="customModalButtons" size="md">
      <template>
        <div class="d-flex justify-content-between pb-3">
          <h6>
            <span class="text-capitalize">{{ formatAssetType(assetType) }}:</span> {{ assetName }}
          </h6>
          <required></required>
        </div>
        <project-select 
          required 
          :label="$t('Select Project')"
          v-model="projects" 
          api-get="projects" 
          api-list="projects"
          name="project" 
          :errors="addError.project" 
        />
        <b-form-group>
          <b-form-checkbox v-model="copyAsset" class="pt-3">
            <span v-b-tooltip.hover.bottom :title="$t('Use a copy if you are planning on making changes to this asset.')">
              Use a copy of this asset
            </span>
          </b-form-checkbox>
        </b-form-group>
      </template>
    </modal>
  </div>
</template>
  
<script>

import FormErrorsMixin from "./FormErrorsMixin";
import Modal from "./Modal";
import Required from "./Required";
import ProjectSelect from "./ProjectSelect";

export default {
  components: { Modal, ProjectSelect, Required },
  mixins: [FormErrorsMixin],
  props: ["assetName", "currentUserId", 'assetType', 'assetId', 'assignedProjects'],
  data() {
    return {
      errors: {},
      projects: [],
      assigned: [],
      copyAsset: false,
      addError: {},
      showModal: false,
      disabled: true,
      customModalButtons: [
        { "content": "Cancel", "action": "close", "variant": "outline-secondary", "disabled": false, "hidden": false },
        { "content": "Add", "action": "addToProject", "variant": "primary", "disabled": true, "hidden": false },
      ],
    }
  },
  computed: {
    title() {
      return this.$t('Add to a Project');
    },
  },
  watch: {
    projects() {
      this.customModalButtons[1].disabled = this.projects.length > 0 ? false : true;
    },
    // assignedProjects() {
    //   this.projects = this.assignedProjects.map(project => project.id);
    // }
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
      this.copyAsset = false;
    },
    validateProject() {
      //TODO: ADD FUNCTIONALITY TO CHECK IF ASSET EXISTS ON A PROJECT
    },
    addToProject() {
      // TODO: ADD FUNCTIONALITY FOR COPYING AN ASSET
      let formData = new FormData();
      formData.append("asset_type", this.assetType);
      formData.append("asset_id", this.assetId);
      formData.append("projects", this.projects);
      this.customModalButtons[1].disabled = true;
      // Verify if the asset was assigned
      ProcessMaker.apiClient.post("projects/assets/verify-assign", formData)
        .then((verifyResponse) => {
          // Review the response
          const isAssigned = verifyResponse?.data?.verify;
          const action = isAssigned ? 'update' : 'create';
          // Add the action in the formData
          formData.append("action", action);
          // Assign the asset
          return ProcessMaker.apiClient.post("/projects/assets/assign", formData);
      })
      .then((assignResponse) => {
        // Show success message and close
        ProcessMaker.alert(assignResponse.data.message, "success");
        this.close();
      })
      .catch((error) => {
        this.errors = error.response.data;
        this.customModalButtons[1].disabled = false;
        if (this.errors.hasOwnProperty('errors')) {
          this.errors = this.errors.errors;
        } else {
          const errorMessage = error.response.data.error;
          ProcessMaker.alert(this.$t(errorMessage), "danger");
        }
      });
    },
    formatAssetType(assetType) {
      return assetType.replace(/-/g, " ");
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
  