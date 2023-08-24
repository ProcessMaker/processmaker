<template>
  <div>
    <b-button :aria-label="$t('Create Script')" v-b-modal.createScript class="mb-3 mb-md-0 ml-md-2">
      <i class="fas fa-plus"></i> {{ $t('Script') }}
    </b-button>
    <modal id="createScript" :title="$t('Create Script')" :ok-disabled="disabled" @ok.prevent="onSubmit" @hidden="onClose">
      <template v-if="countCategories">
        <required></required>
        <b-form-group
          required
          :label="$t('Name')"
          :description="formDescription('The script name must be unique.', 'title', addError)"
          :invalid-feedback="errorMessage('title', addError)"
          :state="errorState('title', addError)"
        >
          <b-form-input
            required
            autofocus
            v-model="title"
            autocomplete="off"
            :state="errorState('title', addError)"
            name="title"
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
            rows="2"
            :state="errorState('description', addError)"
            name="description"
          ></b-form-textarea>
        </b-form-group>
        <category-select :label="$t('Category')" api-get="script_categories"
          api-list="script_categories" v-model="script_category_id"
          :errors="addError.script_category_id"
          name="script_category_id"
        ></category-select>
        <project-select
          v-if="isProjectsInstalled"
          :label="$t('Project')"
          api-get="projects"
          api-list="projects"
          v-model="projects"
          :errors="addError.projects"
          name="project"
        ></project-select>
        <b-form-group
          required
          :label="$t('Language')"
          :invalid-feedback="errorMessage('script_executor_id', addError)"
          :state="errorState('script_executor_id', addError)"
        >
          <b-form-select
            required
            v-model="script_executor_id"
            :options="scriptExecutors"
            :state="errorState('script_executor_id', addError)"
            name="script_executor_id"
          ></b-form-select>
        </b-form-group>
        <b-form-group
          required
          :label="$t('Run script as')"
          :description="formDescription('Select a user to set the API access of the Script', 'run_as_user_id', addError)"
          :invalid-feedback="errorMessage('run_as_user_id', addError)"
          :state="errorState('run_as_user_id', addError)"
        >
          <select-user
            v-model="selectedUser"
            :multiple="false"
            :class="{'is-invalid': errorState('run_as_user_id', addError) == false}"
            name="run_as_user_id"
          ></select-user>
        </b-form-group>
        <slider-with-input
          :label="$t('Timeout')"
          :description="$t('How many seconds the script should be allowed to run (0 is unlimited).')"
          :value="timeout"
          @input="timeout = $event"
          :error="errorState('timeout', addError) ? null : errorMessage('timeout', addError)"
          :min="0"
          :max="300"
        ></slider-with-input>
        <slider-with-input
          :label="$t('Retry Attempts')"
          :description="$t('Number of times to retry. Leave empty to use script default. Set to 0 for no retry attempts.')"
          :value="retry_attempts"
          @input="retry_attempts = $event"
          :error="errorState('retry_attempts', addError) ? null : errorMessage('retry_attempts', addError)"
          :min="0"
          :max="10"
        ></slider-with-input>
        <slider-with-input
          :label="$t('Retry Wait Time')"
          :description="$t('Seconds to wait before retrying. Leave empty to use script default. Set to 0 for no retry wait time.')"
          :value="retry_wait_time"
          @input="retry_wait_time = $event"
          :error="errorState('retry_wait_time', addError) ? null : errorMessage('retry_wait_time', addError)"
          :min="0"
          :max="3600"
        ></slider-with-input>
        <component
          v-for="(cmp,index) in createScriptHooks"
          :key="`create-script-hook-${index}`"
          :is="cmp"
          :script="script"
          ref="createScriptHooks"
        ></component>
      </template>
      <template v-else>
        <div>{{ $t('Categories are required to create a script') }}</div>
        <a href="/designer/scripts/categories" class="btn btn-primary container mt-2">
            {{ $t('Add Category') }}
        </a>
      </template>
    </modal>
  </div>
</template>

<script>
  import { FormErrorsMixin, Modal, Required } from "SharedComponents";
  import SliderWithInput from "../../../components/shared/SliderWithInput";
  import ProjectSelect from "../../../components/shared/ProjectSelect.vue";

  export default {
    components: { Modal, Required, SliderWithInput, ProjectSelect },
    mixins: [ FormErrorsMixin ],
    props: ["countCategories", "scriptExecutors", 'isProjectsInstalled'],
    data: function() {
      return {
        title: '',
        language: '',
        script_executor_id: null,
        description: '',
        script_category_id: '',
        code: '',
        addError: {},
        selectedUser: '',
        users: [],
        timeout: 60,
        retry_attempts: 0,
        retry_wait_time: 5,
        disabled: false,
        createScriptHooks: [],
        script: null,
        projects: [],
      }
    },
    methods: {
      onClose() {
        this.title = '';
        this.language = '';
        this.script_executor_id = null;
        this.description = '';
        this.script_category_id = '';
        this.code = '';
        this.timeout = 60;
        this.retry_attempts = 0;
        this.retry_wait_time = 5;
        this.addError = {};
      },
      onSubmit() {
        this.errors = Object.assign({}, {
          name: null,
          description: null,
          status: null,
          script_category_id: null
        });
        //single click
        if (this.disabled) {
          return
        }
        this.disabled = true;

        //TODO: ADD SCRIPT TO PROJECT IF PROJECT SELECTED

        ProcessMaker.apiClient.post("/scripts", {
          title: this.title,
          script_executor_id: this.script_executor_id,
          description: this.description,
          script_category_id: this.script_category_id,
          run_as_user_id: this.selectedUser ? this.selectedUser.id : null,
          projects: this.projects,
          code: "[]",
          timeout: this.timeout,
          retry_attempts: this.retry_attempts,
          retry_wait_time: this.retry_wait_time
        })
          .then(response => {
            ProcessMaker.alert(this.$t('The script was created.'), 'success');
            (this.$refs.createScriptHooks || []).forEach(hook => {
              hook.onsave(response.data);
            });
            window.location = "/designer/scripts/" + response.data.id + "/builder";
          })
          .catch(error => {
            this.disabled = false;
            if (_.get(error, 'response.status') === 422) {
              this.addError = error.response.data.errors;
            } else {
                throw error;
            }
          })
      }
    }
  };
</script>
