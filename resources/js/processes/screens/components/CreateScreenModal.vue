<template>
  <div>
    <b-button :aria-label="$t('Create Screen')" v-b-modal.createScreen class="mb-3 mb-md-0 ml-md-2">
      <i class="fas fa-plus"></i> {{ $t('Screen') }}
    </b-button>
    <modal id="createScreen" :title="$t('Create Screen')" :ok-disabled="disabled" @ok.prevent="onSubmit" @hidden="onClose">
      <template v-if="countCategories">
        <required></required>
        <b-form-group
          required
          :label="$t('Name')"
          :description="formDescription('The screen name must be unique.', 'title', errors)"
          :invalid-feedback="errorMessage('title', errors)"
          :state="errorState('title', errors)"
        >
          <b-form-input
            required
            autofocus
            v-model="formData.title"
            autocomplete="off"
            :state="errorState('title', errors)"
            name="title"
          ></b-form-input>
        </b-form-group>
        <b-form-group
          required
          :label="$t('Description')"
          :invalid-feedback="errorMessage('description', errors)"
          :state="errorState('description', errors)"
        >
          <b-form-textarea
            required
            v-model="formData.description"
            autocomplete="off"
            rows="3"
            :state="errorState('description', errors)"
            name="description"
          ></b-form-textarea>
        </b-form-group>
        <b-form-group
          required
          :label="$t('Type')"
          :invalid-feedback="errorMessage('type', errors)"
          :state="errorState('type', errors)"
        >
          <b-form-select
            required
            v-model="formData.type"
            :options="types"
            :state="errorState('type', errors)"
            name="type"
          ></b-form-select>
        </b-form-group>
        <category-select :label="$t('Category')" api-get="screen_categories" api-list="screen_categories" v-model="formData.screen_category_id" :errors="errors.screen_category_id"></category-select>
        <project-select
          :label="$t('Project')"
          api-get="projects"
          api-list="projects"
          v-model="formData.projects"
          :errors="errors.projects"
        ></project-select>
      </template>
      <template v-else>
        <div>{{ $t('Categories are required to create a screen') }}</div>
        <a href="/designer/screens/categories" class="btn btn-primary container mt-2">
            {{ $t('Add Category') }}
        </a>
      </template>
    </modal>
  </div>
</template>

<script>
  import { FormErrorsMixin, Modal, Required } from "SharedComponents";
  import ProjectSelect from "../../../components/shared/ProjectSelect.vue";

  export default {
    components: { Modal, Required, ProjectSelect },
    mixins: [ FormErrorsMixin ],
    props: ["countCategories", "types"],
    data() {
      return {
        formData: {},
        errors: {
          'title': null,
          'type': null,
          'description': null,
          'category': null,
        },
        disabled: false,
        projects: [],
      }
    },
    mounted() {
      this.resetFormData();
      this.resetErrors();
    },
    methods: {
      resetFormData() {
        this.formData = Object.assign({}, {
          title: null,
          type: '',
          description: null,
        });
      },
      resetErrors() {
        this.errors = Object.assign({}, {
          title: null,
          type: null,
          description: null,
        });
      },
      onClose() {
        this.resetFormData();
        this.resetErrors();
      },
      onSubmit() {
        this.resetErrors();
        //single click
        if (this.disabled) {
          return
        }
        this.disabled = true;

        //TODO: ADD SCREEN TO PROJECT IF PROJECT SELECTED
        ProcessMaker.apiClient.post('screens', this.formData)
          .then(response => {
            ProcessMaker.alert(this.$t('The screen was created.'), 'success');
            window.location = '/designer/screen-builder/' + response.data.id + '/edit';
          })
          .catch(error => {
            this.disabled = false;
            if (error.response.status && error.response.status === 422) {
              this.errors = error.response.data.errors;
            }
          });
      }
    }
  };
</script>
