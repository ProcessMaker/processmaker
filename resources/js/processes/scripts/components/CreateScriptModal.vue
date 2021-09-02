<template>
  <div>
    <b-button :aria-label="$t('Create Script')" v-b-modal.createScript class="mb-3 mb-md-0 ml-md-2">
      <i class="fas fa-plus"></i> {{ $t('Script') }}
    </b-button>
    <modal id="createScript" :title="$t('Create Script')" :ok-disabled="disabled" @ok.prevent="onSubmit" @hidden="onClose">
      <template v-if="countCategories">
        <b-form-group
          required
          :label="$t('Name')"
          :description="formDescription('The script name must be distinct.', 'title', addError)"
          :invalid-feedback="errorMessage('title', addError)"
          :state="errorState('title', addError)"
        >
          <b-form-input
            autofocus
            v-model="title"
            autocomplete="off"
            :state="errorState('title', addError)"
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
            rows="2"
            :state="errorState('description', addError)"
          ></b-form-textarea>
        </b-form-group>
        <category-select :label="$t('Category')" api-get="script_categories"
          api-list="script_categories" v-model="script_category_id"
          :errors="addError.script_category_id" ref="categorySelect"
        ></category-select>
        <b-form-group
          required
          :label="$t('Language')"
          :invalid-feedback="errorMessage('script_executor_id', addError)"
          :state="errorState('script_executor_id', addError)"
        >
          <b-form-select
            v-model="script_executor_id"
            :options="scriptExecutors"
            :state="errorState('script_executor_id', addError)"
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
          ></select-user>
        </b-form-group>
        <b-form-group
          :label="$t('Timeout')"
          label-for="script-timeout-text"
          :description="formDescription('Enter how many seconds the Script runs before timing out (0 is unlimited).', 'timeout', addError)"
          :invalid-feedback="errorMessage('timeout', addError)"
          :state="errorState('timeout', addError)"
        >
          <div class="d-flex align-items-center w-100">
            <b-form-input
              v-model="timeout"
              class="w-25"
              type="number"
              id="script-timeout-text"
            ></b-form-input>
            <b-form-input
              v-model="timeout"
              type="range"
              min="0"
              max="300"
              :state="errorState('timeout', addError)"
              class="ml-3 w-100"
            ></b-form-input>
          </div>
        </b-form-group>
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

  export default {
    components: { Modal, Required },
    mixins: [ FormErrorsMixin ],
    props: ["countCategories", "scriptExecutors"],
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
        disabled: false,
        createScriptHooks: [],
        script: null,
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
        this.addError = {};
        this.$refs.categorySelect.resetUncategorized();
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
        ProcessMaker.apiClient.post("/scripts", {
          title: this.title,
          script_executor_id: this.script_executor_id,
          description: this.description,
          script_category_id: this.script_category_id,
          run_as_user_id: this.selectedUser ? this.selectedUser.id : null,
          code: "[]",
          timeout: this.timeout
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
