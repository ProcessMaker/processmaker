<template>
  <div>
    <div v-if="hasAuthorizedBadge">
      <b-badge
        pill
        :variant="isAuthorized ? 'success' : 'warning'"
      >
        <span v-if="isAuthorized">{{ $t('Authorized') }}</span>
        <span v-else>{{ $t('Not Authorized') }}</span>
      </b-badge>
    </div>
    <div v-else>
      Empty
    </div>
    <b-modal
      v-model="showModal"
      class="setting-object-modal"
      size="lg"
      centered
      @hidden="onModalHidden"
    >
      <template
        #modal-header
        class="d-block"
      >
        <div>
          <h5
            v-if="setting.name"
            class="mb-0"
          >
            {{ $t(setting.name) }}
          </h5>
          <h5
            v-else
            class="mb-0"
          >
            {{ setting.key }}
          </h5>
          <small class="form-text text-muted">{{ $t('Configure the driver connection properties.') }}</small>
        </div>
        <button
          type="button"
          :aria-label="$t('Close')"
          class="close"
          @click="onCancel"
        >
          ×
        </button>
      </template>
      <div>
        <b-form-group
          required
          :label="$t('Client ID')"
          :description="formDescription('The client ID assigned when you register your application.', 'client_id', errors)"
          :invalid-feedback="errorMessage('client_id', errors)"
          :state="errorState('client_id', errors)"
        >
          <b-form-input
            v-model="formData.client_id"
            required
            autofocus
            autocomplete="off"
            :state="errorState('client_id', errors)"
            name="client_id"
          />
        </b-form-group>

        <b-form-group
          required
          :label="$t('Client Secret')"
          :description="formDescription('The client secret assigned when you register your application.', 'client_secret', errors)"
          :invalid-feedback="errorMessage('client_secret', errors)"
          :state="errorState('client_secret', errors)"
        >
          <b-input-group>
            <b-form-input
              v-model="formData.client_secret"
              required
              autofocus
              autocomplete="off"
              trim
              :type="type"
              :state="errorState('client_secret', errors)"
              name="client_secret"
            />
            <b-input-group-append>
              <b-button
                :aria-label="$t('Toggle Show Password')"
                variant="secondary"
                @click="togglePassword"
              >
                <i
                  class="fas"
                  :class="icon"
                />
              </b-button>
            </b-input-group-append>
          </b-input-group>
        </b-form-group>

        <b-form-group
          required
          :label="$t('Redirect URL')"
          :description="formDescription('This value must match the callback URL you specify in your app settings.', 'callback_url', errors)"
          :invalid-feedback="errorMessage('callback_url', errors)"
          :state="errorState('callback_url', errors)"
        >
          <b-input-group>
            <b-form-input
              v-model="formData.callback_url"
              autofocus
              readonly
              autocomplete="off"
              :state="errorState('callback_url', errors)"
              name="callback_url"
            />
            <b-input-group-append>
              <b-button
                :aria-label="$t('Copy')"
                variant="secondary"
                @click="onCopy"
              >
                <i class="fas fa-copy" />
              </b-button>
            </b-input-group-append>
          </b-input-group>
        </b-form-group>

        <additional-driver-connection-properties
          :driver-key="setting?.key"
          :form-data="formData"
          @updateFormData="updateFormData"
        />
      </div>
      <div
        slot="modal-footer"
        class="w-100 m-0 d-flex"
      >
        <button
          type="button"
          class="btn btn-outline-secondary ml-auto"
          @click="onCancel"
        >
          {{ $t('Cancel') }}
        </button>
        <button
          type="button"
          class="btn btn-secondary ml-3"
          :disabled="isButtonDisabled"
          @click="onSave"
        >
          {{ $t('Authorize') }}
        </button>
      </div>
    </b-modal>

    <b-modal
      v-model="showAuthorizingModal"
      class="setting-object-modal"
      size="md"
      hide-footer
      hide-header
      centered
      no-fade
    >
      <div class="text-center">
        <h3>{{ $t('Connecting Driver') }}</h3>
        <i class="fas fa-circle-notch fa-spin fa-3x p-0 text-primary" />
      </div>
    </b-modal>
  </div>
</template>

<script>
// eslint-disable-next-line import/no-unresolved
import { FormErrorsMixin, Required } from "SharedComponents";
import settingMixin from "../mixins/setting";
import AdditionalDriverConnectionProperties from "./AdditionalDriverConnectionProperties.vue";

export default {
  components: { AdditionalDriverConnectionProperties },
  mixins: [settingMixin, FormErrorsMixin, Required],
  props: {
    setting: {
      type: [Object, null],
      default: null,
    },
    value: {
      type: Object,
      default: null,
    },
  },
  data() {
    return {
      input: "",
      formData: {
        client_id: "",
        client_secret: "",
        callback_url: "",
      },
      selected: null,
      showModal: false,
      showAuthorizingModal: false,
      transformed: null,
      errors: {},
      isInvalid: true,
      type: "password",
      resetData: true,
    };
  },
  computed: {
    hasAuthorizedBadge() {
      if (!this.setting) {
        return false;
      }
      const hasAuthorizedBadge = !!_.has(this.setting, "ui.authorizedBadge");
      return hasAuthorizedBadge;
    },
    isAuthorized() {
      if (this.hasAuthorizedBadge) {
        return Boolean(this.setting.ui.authorizedBadge);
      }
      return false;
    },
    changed() {
      return JSON.stringify(this.formData) !== JSON.stringify(this.transformed);
    },
    icon() {
      if (this.type === "password") {
        return "fa-eye";
      }
      return "fa-eye-slash";
    },
    isButtonDisabled() {
      return this.isInvalid || (this.isAuthorized && !this.changed);
    },
  },
  watch: {
    formData: {
      handler() {
        this.isInvalid = this.validateData();
      },
      deep: true,
    },
  },
  mounted() {
    if (this.value === null) {
      this.resetFormData();
    } else {
      this.formData = this.value;
    }
    this.isInvalid = this.validateData();
    this.transformed = this.copy(this.formData);
  },
  methods: {
    onCopy() {
      navigator.clipboard.writeText(this.formData.callback_url).then(() => {
        ProcessMaker.alert(this.$t("The setting was copied to your clipboard."), "success");
      }, () => {
        ProcessMaker.alert(this.$t("The setting was not copied to your clipboard."), "danger");
      });
    },
    togglePassword() {
      if (this.type === "text") {
        this.type = "password";
      } else {
        this.type = "text";
      }
    },
    validateData() {
      // Check if client_id and client_secret are empty
      const clientIdEmpty = _.isEmpty(this.formData.client_id);
      const clientSecretEmpty = _.isEmpty(this.formData.client_secret);

      return _.isEmpty(this.formData) || clientIdEmpty || clientSecretEmpty;
    },
    onCancel() {
      this.showModal = false;
    },
    onEdit(row) {
      if (this.value !== null) {
        this.formData = this.value;
      }
      this.generateCallbackUrl(row.item);
      this.$nextTick(() => {
        this.showModal = true;
      });
    },
    onModalHidden() {
      this.resetFormData();
    },
    authorizeConnection() {
      this.showAuthorizingModal = true;
      this.showModal = false;
      this.resetData = false;
      ProcessMaker.apiClient.post(`settings/${this.setting.id}/get-oauth-url`, this.formData)
        .then((response) => {
          window.location = response.data?.url;
        })
        .catch((error) => {
          const errorMessage = error.response?.data?.message || error.message;
          ProcessMaker.alert(errorMessage, "danger");
          this.showModal = true;
          this.showAuthorizingModal = false;
        });
    },
    onSave() {
      const driver = this.setting.key.split("cdata.")[1];

      this.formData.driver = driver;
      this.transformed = { ...this.formData };
      this.authorizeConnection();
    },
    generateCallbackUrl(data) {
      const name = data.key.split("cdata.")[1];
      const appUrl = document.head.querySelector("meta[name=\"app-url\"]").content;

      this.formData.callback_url = `${appUrl}/external-integrations/${name}`;
    },
    resetFormData() {
      if (this.resetData) {
        this.formData = {
          client_id: "",
          client_secret: "",
          callback_url: "",
        };
      }
    },
    updateFormData(val) {
      this.formData = { ...this.formData, ...val };
    },
  },
};
</script>
