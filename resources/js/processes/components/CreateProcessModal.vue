<template>
  <div>
    <b-button :aria-label="$t('Create Process')" v-b-modal.createProcess class="mb-3 mb-md-0 ml-md-2">
      <i class="fas fa-plus"></i> {{ $t('Process') }}
    </b-button>
    <modal id="createProcess" :title="$t('Create Process')" :ok-disabled="disabled" @ok.prevent="onSubmit" @hidden="onClose">
      <template v-if="countCategories">
        <b-form-group
          required
          :label="$t('Name')"
          :description="formDescription('The process name must be distinct', 'name', addError)"
          :invalid-feedback="errorMessage('name', addError)"
          :state="errorState('name', addError)"
        >
          <b-form-input
            autofocus
            v-model="name"
            autocomplete="off"
            :state="errorState('name', addError)"
          ></b-form-input>
        </b-form-group>
        <b-form-group
          required
          :label="$t('Description')"
          :invalid-feedback="errorMessage('description', addError)"
          :state="errorState('description', addError)"
        >
          <b-form-textarea
            v-model="description"
            autocomplete="off"
            rows="3"
            :state="errorState('description', addError)"
          ></b-form-textarea>
        </b-form-group>
        <category-select :label="$t('Category')" api-get="process_categories"
          api-list="process_categories" v-model="process_category_id"
          :errors="addError.process_category_id" ref="categorySelect"
        ></category-select>
        <b-form-group
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

  export default {
    components: { Modal, Required },
    mixins: [ FormErrorsMixin ],
    props: ["countCategories"],
    data: function() {
      return {
        showModal: false,
        name: "",
        selectedFile: "",
        categoryOptions: "",
        description: "",
        process_category_id: "",
        addError: {},
        status: "",
        bpmn: "",
        disabled: false
      }
    },
    methods: {
      browse () {
        this.$refs.customFile.click();
      },
      onFileChange (e) {
        let files = e.target.files || e.dataTransfer.files;
        console.log('onFileChange', files);

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
        this.$refs.categorySelect.resetUncategorized();
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
        if (this.file) {
          formData.append("file", this.file);
        }

        ProcessMaker.apiClient.post("/processes", formData,
          {
            headers: {
              "Content-Type": "multipart/form-data"
            }
          })
          .then(response => {
            ProcessMaker.alert(this.$t('The process was created.'), "success");
            window.location = "/modeler/" + response.data.id;
          })
          .catch(error => {
            this.disabled = false;
            this.addError = error.response.data.errors;
          });
      }
    }
  };
</script>
