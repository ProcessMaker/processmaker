<template>
  <div>
    <b-button :aria-label="$t('Create Environment Variable')" v-b-modal.createEnvironmentVariable class="mb-3 mb-md-0 ml-md-2">
      <i class="fas fa-plus"></i> {{ $t('Environment Variable') }}
    </b-button>
    <modal id="createEnvironmentVariable" :title="$t('Create Environment Variable')" :ok-disabled="disabled" @ok.prevent="onSubmit" @hidden="onClose">
      <required></required>
      <b-form-group
        required
        :label="$t('Name')"
        :description="formDescription('The environment variable name must be unique.', 'name', errors)"
        :invalid-feedback="errorMessage('name', errors)"
        :state="errorState('name', errors)"
      >
        <b-form-input
          required
          autofocus
          v-model="name"
          autocomplete="off"
          :state="errorState('name', errors)"
          name="name"
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
          v-model="description"
          autocomplete="off"
          rows="3"
          :state="errorState('description', errors)"
          name="description"
        ></b-form-textarea>
      </b-form-group>
      <asset-link-fields
        :asset-type.sync="assetType"
        :value.sync="value"
        :errors="errors"
      />
      <do-not-update-switch v-model="doNotUpdate" />
    </modal>
  </div>
</template>

<script>
  import  FormErrorsMixin  from "../../../components/shared/FormErrorsMixin.js";
  import Modal from "../../../components/shared/Modal.vue";
  import Required from "../../../components/shared/Required.vue";
  import AssetLinkFields from "./AssetLinkFields.vue";
  import DoNotUpdateSwitch from "./DoNotUpdateSwitch.vue";

  export default {
    components: { Modal, Required, AssetLinkFields, DoNotUpdateSwitch },
    mixins: [ FormErrorsMixin ],
    data: function() {
      return {
        errors: {},
        name: '',
        description: '',
        value: '',
        assetType: null,
        doNotUpdate: false,
        disabled: false,
      }
    },
    methods: {
      onClose() {
        this.name = '';
        this.description = '';
        this.value = '';
        this.assetType = null;
        this.doNotUpdate = false;
        this.errors = {};
        this.disabled = false;
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
          value: this.value,
          asset_type: this.assetType,
          do_not_update: this.doNotUpdate,
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
