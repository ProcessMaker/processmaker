<template>
  <div>
    <b-button :aria-label="$t('Create Environment Variable')" v-b-modal.createEnvironmentVariable class="mb-3 mb-md-0 ml-md-2">
      <i class="fas fa-plus"></i> {{ $t('Environment Variable') }}
    </b-button>
    <modal id="createEnvironmentVariable" :title="$t('Create Environment Variable')" :ok-disabled="disabled" @ok.prevent="onSubmit" @hidden="onClose">
      <b-form-group
        required
        :label="$t('Name')"
        :description="formDescription('The environment variable name must be distinct.', 'name', errors)"
        :invalid-feedback="errorMessage('name', errors)"
        :state="errorState('name', errors)"
      >
        <b-form-input
          autofocus
          v-model="name"
          autocomplete="off"
          :state="errorState('name', errors)"
        ></b-form-input>
      </b-form-group>
      <b-form-group
        required
        :label="$t('Description')"
        :invalid-feedback="errorMessage('description', errors)"
        :state="errorState('description', errors)"
      >
        <b-form-textarea
          v-model="description"
          autocomplete="off"
          rows="3"
          :state="errorState('description', errors)"
        ></b-form-textarea>
      </b-form-group>
      <b-form-group
        :label="$t('Value')"
        :invalid-feedback="errorMessage('value', errors)"
        :state="errorState('value', errors)"
      >
        <b-form-textarea
          v-model="value"
          autocomplete="off"
          rows="10"
          :state="errorState('value', errors)"
        ></b-form-textarea>
      </b-form-group>
    </modal>
  </div>
</template>

<script>
  import { FormErrorsMixin, Modal, Required } from "SharedComponents";

  export default {
    components: { Modal, Required },
    mixins: [ FormErrorsMixin ],
    data: function() {
      return {
        errors: {},
        name: '',
        description: '',
        value: '',
        disabled: false,
      }
    },
    methods: {
      onClose() {
        this.name = '';
        this.description = '';
        this.value = '';
        this.errors = {};
      },
      onSubmit() {
        this.errors = {};
        //single click
        if (this.disabled) {
          return
        }
        this.disabled = true;
        ProcessMaker.apiClient.post('environment_variables', {
          name: this.name,
          description: this.description,
          value: this.value
        })
          .then(response => {
            ProcessMaker.alert(this.$t('The environment variable was created.'), 'success');
            window.location = '/designer/environment-variables';
          })
          .catch(error => {
            this.disabled = false;
            if (error.response.status === 422) {
              this.errors = error.response.data.errors
            }
          });
      }
    }
  };
</script>
