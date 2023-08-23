<template>
  <div>
    <modal 
      id="createProcess"
      :title="$t('Create Process')"
      :ok-disabled="disabled"
      @ok.prevent="onSubmit"
      @hidden="onClose"
      @shown="onShown()"
    >
      <template v-if="countCategories">
        <required></required>
        <b-form-group
          required
          :label="$t('Name')"
          :description="formDescription('The process name must be unique', 'name', addError)"
          :invalid-feedback="errorMessage('name', addError)"
          :state="errorState('name', addError)"
        >
          <b-form-input
            autofocus
            v-model="name"
            autocomplete="off"
            :state="errorState('name', addError)"
            name="name"
            required
          ></b-form-input>
        </b-form-group>
        <b-form-group
          required
          :label="$t('Description')"
          :invalid-feedback="errorMessage('description', addError)"
          :state="errorState('description', addError)"
        >
          <b-form-textarea
            required
            v-model="description"
            autocomplete="off"
            rows="3"
            :state="errorState('description', addError)"
            name="description"
          ></b-form-textarea>
        </b-form-group>
        <category-select :label="$t('Category')" api-get="process_categories"
          api-list="process_categories" v-model="process_category_id"
          :errors="addError?.process_category_id"
          name="category"
        ></category-select>
        <project-select 
          :label="$t('Project')"
          api-get="projects"
          api-list="projects"
          v-model="projects"
          :errors="addError?.projects"
          name="project"
        ></project-select>
       <b-form-group
          :label="$t('Process Manager')"
        >
          <select-user
            :multiple="false"
            v-model="manager"
            name="process_manager_id"
          ></select-user>
        </b-form-group>
        <b-form-group
          v-if="!selectedTemplate && !generativeProcessData"
          :label="$t('Upload BPMN File (optional)')"
          :invalid-feedback="errorMessage('bpmn', addError)"
          :state="errorState('bpmn', addError)"
        >
          <b-form-file
            :browse-text="$t('Browse')"
            accept=".bpmn,.xml"
            :placeholder="selectedFile"
            ref="customFile"
            @change="onFileChange"
            :state="errorState('bpmn', addError)"
          ></b-form-file>
        </b-form-group>
      </template>
      <template v-else>
        <div>{{ $t('Categories are required to create a process') }}</div>
        <a href="/designer/processes/categories" class="btn btn-primary container mt-2">
          {{ $t('Add Category') }}
        </a>
      </template>
    </modal>
  </div>
</template>

<script>
  import { FormErrorsMixin, Modal, Required } from "SharedComponents";
  import TemplateSearch from "../../components/templates/TemplateSearch.vue";
  import ProjectSelect from "../../components/shared/ProjectSelect.vue";

  export default {
    components: { Modal, Required, TemplateSearch, ProjectSelect },
    mixins: [ FormErrorsMixin ],
    props: ["countCategories", "blankTemplate", "selectedTemplate", "templateData", "generativeProcessData"],
    data: function() {
      return {
        showModal: false,
        name: "",
        selectedFile: "",
        categoryOptions: "",
        description: "",
        process_category_id: "",
        projects: [],
        template_version: null,
        addError: {},
        status: "",
        bpmn: "",
        disabled: false,
        customModalButtons: [
            {'content': 'Cancel', 'action': 'hide()', 'variant': 'outline-secondary', 'disabled': false, 'hidden': false},
            {'content': 'Create', 'action': 'createTemplate', 'variant': 'primary', 'disabled': false, 'hidden': false},
        ],
        manager: "",
      }
    },
    watch: {
      selectedTemplate: function() {
        if (this.selectedTemplate) {
          this.name = this.templateData.name;
          this.description = this.templateData.description;  
          this.process_category_id = this.templateData.category_id;
          this.template_version = this.templateData.version;
        }
      },
      manager: function() {
        if (!this.manager) {
          this.manager = "";
        }
      },
    },
    methods: {
      onShown() {
        if (this.generativeProcessData) {
          this.name = this.generativeProcessData.process_title;
          this.description = this.generativeProcessData.process_description;
        }
      },
      show() {
      this.$bvModal.show("createProcess");
      },
      browse () {
        this.$refs.customFile.click();
      },
      onFileChange (e) {
        let files = e.target.files || e.dataTransfer.files;

        if (!files.length) {
          return;
        }

        this.selectedFile = files[0].name;
        this.file = files[0];
      },
      onClose () {
        this.name = "";
        this.description = "";
        this.process_category_id = "";
        this.status = "";
        this.addError = {};
        this.selectedFile = '';
        this.file = null;
        this.manager = "";
        this.$emit('resetModal');
      },
      onSubmit () {
        this.errors = Object.assign({}, {
          name: null,
          description: null,
          process_category_id: null,
          status: null
        });
        if (this.process_category_id === "") {
          this.addError = {"process_category_id": ["{{__('The category field is required.')}}"]};
          return;
        }
        //single click
        if (this.disabled) {
          return;
        }
        this.disabled = true;
        
        let formData = new FormData();
        formData.append("name", this.name);
        formData.append("description", this.description);
        formData.append("process_category_id", this.process_category_id);
        formData.append("manager_id", this.manager.id);
        if (this.file) {
          formData.append("file", this.file);
        }
        if (this.selectedTemplate) {
          formData.append("version", this.template_version);
          this.handleCreateFromTemplate(this.templateData.id, formData);
        } else {
          if (this.generativeProcessData) {
            formData.append("bpmn", this.generativeProcessData.bpmn);
          }
          this.handleCreateBlank(formData);
        }
      },
      handleCreateFromTemplate(id, formData) {
        ProcessMaker.apiClient.post(`template/create/process/${id}`, formData,
        {
          headers: {
            "Content-Type": "multipart/form-data"
          }
        })
        .then(response => {
          ProcessMaker.alert(this.$t('The process was created.'), "success");
          window.location = "/modeler/" + response.data.processId;
        })
        .catch(error => {
          this.disabled = false;
          this.addError = error.response.data.errors;
        });
      },
      handleCreateBlank(formData) {
        ProcessMaker.apiClient.post("/processes", formData,
        {
          headers: {
            "Content-Type": "multipart/form-data"
          }
        })
        .then(response => {
          if (this.generativeProcessData) {
            this.$emit("clear-ai-history");
          }
          ProcessMaker.alert(this.$t('The process was created.'), "success");
          window.location = "/modeler/" + response.data.id;
        })
        .catch(error => {
          this.disabled = false;
          this.addError = error.response.data.errors;
        });
      },
    },
  };
</script>

<style scoped>
  .search-column {
    border-right: 1px solid #b6bfc6;
  }
</style>
